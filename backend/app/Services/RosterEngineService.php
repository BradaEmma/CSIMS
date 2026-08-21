<?php

namespace App\Services;

use App\Models\Guard;
use App\Models\GuardAssignment;
use App\Models\Post;
use App\Models\Site;
use App\Models\RosterAssignment;
use App\Models\RosterGenerationLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RosterEngineService
{
    private array $assignmentCounts = [];

    public function generateWeeklyRoster(string $startDate, ?int $triggeredBy = null): array
    {
        try {
            $result = DB::transaction(function () use ($startDate) {

                $start = Carbon::parse($startDate)->startOfDay();
                $end   = $start->copy()->addDays(6);

                $guards = Guard::where('status', 'active')->get();
                $posts  = Post::where('status', 'active')
                    ->whereHas('site', fn ($q) => $q->where('status', 'active'))
                    ->with('site')
                    ->get();

                $this->loadAssignmentCounts($guards, $start);

                $created = 0;
                $shortages = [];

                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {

                    foreach ($posts as $post) {

                        $requirements = [
                            'morning' => $post->morning_guards_required ?? 0,
                            'night'   => $post->night_guards_required ?? 0,
                        ];

                        foreach ($requirements as $shift => $needed) {

                            $assigned = RosterAssignment::where('post_id', $post->id)
                                ->where('date', $date->toDateString())
                                ->where('shift', $shift)
                                ->count();

                            while ($assigned < $needed) {

                                $guard = $this->findAvailableGuard($guards, $post, $date, $shift);

                                if (!$guard) {
                                    $guard = $this->findOvertimeGuard($guards, $post, $date, $shift);
                                }

                                if (!$guard) {
                                    $shortages[] = [
                                        'date' => $date->toDateString(),
                                        'site' => $post->site->name,
                                        'post' => $post->name,
                                        'shift' => $shift,
                                        'missing' => $needed - $assigned,
                                    ];
                                    break;
                                }

                                $isOvertime = $this->isRestDay($guard->id, $date);

                                RosterAssignment::updateOrCreate(
                                    [
                                        'guard_id' => $guard->id,
                                        'post_id'  => $post->id,
                                        'date'     => $date->toDateString(),
                                        'shift'    => $shift,
                                    ],
                                    [
                                        'site_id' => $post->site_id,
                                        'is_overtime' => $isOvertime,
                                        'generated_by_system' => true,
                                    ]
                                );

                                $this->assignmentCounts[$guard->id] = ($this->assignmentCounts[$guard->id] ?? 0) + 1;

                                $assigned++;
                                $created++;
                            }
                        }
                    }
                }

                return [
                    'start' => $start,
                    'end' => $end,
                    'created' => $created,
                    'shortages' => $shortages,
                ];
            });

            $log = RosterGenerationLog::create([
                'start_date' => $result['start']->toDateString(),
                'end_date' => $result['end']->toDateString(),
                'triggered_by' => $triggeredBy,
                'assignments_created' => $result['created'],
                'shortages_count' => count($result['shortages']),
                'shortages_detail' => $result['shortages'],
                'status' => 'success',
            ]);

            if (count($result['shortages']) > 0) {
                $recipients = \App\Models\User::role(['admin', 'supervisor'])->get();

                \Illuminate\Support\Facades\Notification::send(
                    $recipients,
                    new \App\Notifications\RosterShortageNotification(
                        $result['start']->toDateString(),
                        $result['end']->toDateString(),
                        $result['shortages'],
                        $log->id
                    )
                );
            }

            return [
                'success' => true,
                'created_assignments' => $result['created'],
                'shortages' => $result['shortages'],
            ];

        } catch (\Throwable $e) {

            RosterGenerationLog::create([
                'start_date' => $startDate,
                'end_date' => Carbon::parse($startDate)->addDays(6)->toDateString(),
                'triggered_by' => $triggeredBy,
                'assignments_created' => 0,
                'shortages_count' => 0,
                'shortages_detail' => null,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Roster generation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Manually assign a guard to cover a shift outside their normal
     * preference/rest rules — used when a real shortage exists and a
     * specific guard has explicitly agreed to double up. This bypasses
     * shift-preference matching and the rest-day block on purpose, but
     * still enforces genuine site eligibility and requires a real
     * supervisor/admin ID as proof the guard's consent was recorded.
     *
     * Takes a postId (not siteId) — a double shift covers a specific post,
     * and site eligibility is still checked via the post's parent site.
     */
    public function assignDoubleShift(int $guardId, int $postId, string $date, string $shift, int $confirmedBy): array
    {
        $guard = Guard::find($guardId);
        $post = Post::find($postId);

        if (!$guard || $guard->status !== 'active') {
            return ['success' => false, 'message' => 'Guard not found or not active'];
        }

        if (!$post || $post->status !== 'active') {
            return ['success' => false, 'message' => 'Post not found or not active'];
        }

        $site = $post->site;

        if (!$site || $site->status !== 'active') {
            return ['success' => false, 'message' => 'Site not found or not active'];
        }

        if (!in_array($shift, ['morning', 'night'], true)) {
            return ['success' => false, 'message' => 'Invalid shift — must be morning or night'];
        }

        $parsedDate = Carbon::parse($date);

        if (!$this->isEligibleForSite($guardId, $site->id, $parsedDate)) {
            return ['success' => false, 'message' => 'Guard is not eligible for this site'];
        }

        $existing = RosterAssignment::where('guard_id', $guardId)
            ->where('post_id', $postId)
            ->where('date', $date)
            ->where('shift', $shift)
            ->first();

        if ($existing) {
            return ['success' => false, 'message' => 'Guard is already assigned to this exact shift'];
        }

        $assignment = RosterAssignment::create([
            'guard_id' => $guardId,
            'site_id' => $site->id,
            'post_id' => $postId,
            'date' => $date,
            'shift' => $shift,
            'is_overtime' => $this->isRestDay($guardId, $parsedDate),
            'is_double_shift' => true,
            'consent_confirmed_by' => $confirmedBy,
            'generated_by_system' => false,
        ]);

        return ['success' => true, 'data' => $assignment];
    }

    private function loadAssignmentCounts($guards, Carbon $start): void
    {
        $windowStart = $start->copy()->subDays(30)->toDateString();
        $windowEnd = $start->copy()->subDay()->toDateString();

        $counts = RosterAssignment::whereIn('guard_id', $guards->pluck('id'))
            ->whereBetween('date', [$windowStart, $windowEnd])
            ->selectRaw('guard_id, count(*) as total')
            ->groupBy('guard_id')
            ->pluck('total', 'guard_id');

        $this->assignmentCounts = [];
        foreach ($guards as $guard) {
            $this->assignmentCounts[$guard->id] = $counts[$guard->id] ?? 0;
        }
    }

    private function rankedCandidates($guards, Post $post, Carbon $date, string $shift, bool $requireRestDayOk)
    {
        return $guards
            ->filter(function ($guard) use ($post, $date, $shift, $requireRestDayOk) {
                if (!$this->matchesShiftPreference($guard, $shift)) {
                    return false;
                }
                if (!$this->isEligibleForSite($guard->id, $post->site_id, $date)) {
                    return false;
                }
                if ($this->alreadyAssigned($guard->id, $date)) {
                    return false;
                }
                if ($requireRestDayOk && $this->isRestDay($guard->id, $date)) {
                    return false;
                }
                return true;
            })
            ->sortBy(fn ($guard) => $this->assignmentCounts[$guard->id] ?? 0)
            ->values();
    }

    private function matchesShiftPreference(Guard $guard, string $shift): bool
    {
        return $guard->shift_type === 'either' || $guard->shift_type === $shift;
    }

    private function findAvailableGuard($guards, Post $post, Carbon $date, string $shift)
    {
        return $this->rankedCandidates($guards, $post, $date, $shift, true)->first();
    }

    private function findOvertimeGuard($guards, Post $post, Carbon $date, string $shift)
    {
        return $this->rankedCandidates($guards, $post, $date, $shift, false)->first();
    }

    private function isEligibleForSite(int $guardId, int $siteId, Carbon $date): bool
    {
        return GuardAssignment::where('guard_id', $guardId)
            ->where('site_id', $siteId)
            ->where('status', 'active')
            ->where('start_date', '<=', $date->toDateString())
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date->toDateString());
            })
            ->exists();
    }

    private function isRestDay(int $guardId, Carbon $date): bool
    {
        $workedDays = RosterAssignment::where('guard_id', $guardId)
            ->whereBetween('date', [
                $date->copy()->subDays(6)->toDateString(),
                $date->copy()->subDay()->toDateString(),
            ])
            ->count();

        return $workedDays >= 6;
    }

    private function alreadyAssigned(int $guardId, Carbon $date): bool
    {
        return RosterAssignment::where('guard_id', $guardId)
            ->where('date', $date->toDateString())
            ->exists();
    }
}