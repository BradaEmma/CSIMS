<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Guard;
use App\Models\RosterAssignment;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    const LATE_THRESHOLD_MINUTES = 15;

    public function checkIn(Guard $guard, ?int $siteId, int $recordedBy): array
    {
        return DB::transaction(function () use ($guard, $siteId, $recordedBy) {

            $now = Carbon::now('Africa/Dar_es_Salaam');

            $shiftKey = app(ShiftEngineService::class)->currentShiftKey($now);

            $rosterAssignment = RosterAssignment::where('guard_id', $guard->id)
                ->where('date', $shiftKey['date'])
                ->where('shift', $shiftKey['shift'])
                ->first();

            if (!$rosterAssignment) {
                return [
                    'success' => false,
                    'message' => 'No roster assignment found for this guard for the current shift',
                ];
            }

            $resolvedSiteId = $rosterAssignment->site_id;
            $site = $siteId
                ? Site::find($siteId)
                : Site::find($resolvedSiteId);

            if (!$site) {
                return [
                    'success' => false,
                    'message' => 'Site not found',
                ];
            }

            // prevent duplicate open attendance for THIS roster assignment
            $existing = Attendance::where('guard_id', $guard->id)
                ->where('roster_assignment_id', $rosterAssignment->id)
                ->whereNull('check_out_at')
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Already checked in',
                ];
            }

            $isLate = $now->gt($shiftKey['starts_at']->copy()->addMinutes(self::LATE_THRESHOLD_MINUTES));
            $minutesLate = $isLate ? $shiftKey['starts_at']->diffInMinutes($now) : 0;

            $attendance = Attendance::create([
                'guard_id'              => $guard->id,
                'site_id'               => $site->id,
                'roster_assignment_id'  => $rosterAssignment->id,
                'check_in_at'           => $now,
                'is_late'               => $isLate,
                'minutes_late'          => $minutesLate,
                'status'                => $isLate ? 'late' : 'present',
                'recorded_by'           => $recordedBy,
            ]);

            return [
                'success' => true,
                'data' => $attendance,
            ];
        });
    }

    public function checkOut(Guard $guard, int $recordedBy, ?int $rosterAssignmentId = null): array
    {
        return DB::transaction(function () use ($guard, $recordedBy, $rosterAssignmentId) {

            $query = Attendance::where('guard_id', $guard->id)
                ->whereNull('check_out_at');

            if ($rosterAssignmentId) {
                $query->where('roster_assignment_id', $rosterAssignmentId);
            }

            $attendance = $query->latest()->first();

            if (!$attendance) {
                return [
                    'success' => false,
                    'message' => $rosterAssignmentId
                        ? 'No active check-in found for this roster assignment'
                        : 'No active check-in found',
                ];
            }

            $now = Carbon::now('Africa/Dar_es_Salaam');

            $hours = round(
                $attendance->check_in_at->diffInMinutes($now) / 60,
                2
            );

            $attendance->update([
                'check_out_at' => $now,
                'hours_worked' => $hours,
                'status' => 'completed',
                'recorded_by' => $recordedBy,
            ]);

            return [
                'success' => true,
                'data' => $attendance,
            ];
        });
    }

    public function todaySummary(): array
    {
        $today = Carbon::now('Africa/Dar_es_Salaam')->toDateString();

        $rosteredGuardIds = RosterAssignment::where('date', $today)
            ->pluck('guard_id')
            ->unique();

        $attendanceToday = Attendance::whereDate('check_in_at', $today)->get();

        $checkedInGuardIds = $attendanceToday->pluck('guard_id')->unique();
        $absentGuardIds = $rosteredGuardIds->diff($checkedInGuardIds);

        return [
            'date'             => $today,
            'rostered'         => $rosteredGuardIds->count(),
            'checked_in'       => $checkedInGuardIds->count(),
            'late'             => $attendanceToday->where('is_late', true)->count(),
            'checked_out'      => $attendanceToday->whereNotNull('check_out_at')->count(),
            'absent'           => $absentGuardIds->count(),
            'absent_guard_ids' => $absentGuardIds->values(),
        ];
    }
}