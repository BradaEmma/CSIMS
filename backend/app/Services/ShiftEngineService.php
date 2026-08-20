<?php

namespace App\Services;

use App\Models\ShiftInstance;
use App\Models\Schedule;
use Carbon\Carbon;

class ShiftEngineService
{
    public function generateShiftInstancesForDate(?string $date = null): void
    {
        $date = $date? Carbon::parse($date) : Carbon::now('Africa/Dar_es_Salaam')->startOfDay();

        $schedules = Schedule::where('schedule_date', $date->toDateString())->get();

        foreach ($schedules as $schedule) {

            [$startsAt, $endsAt] = $this->resolveShiftWindow($date, $schedule->shift);

            ShiftInstance::updateOrCreate(
                [
                    'schedule_id' => $schedule->id,
                    'shift_date'  => $date->toDateString(),
                ],
                [
                    'starts_at' => $startsAt,
                    'ends_at'   => $endsAt,
                    'status'    => 'open',
                ]
            );
        }
    }

    private function resolveShiftWindow(Carbon $date, string $shift): array
    {
        return match ($shift) {
            'morning' => [
                $date->copy()->setTime(6, 0),
                $date->copy()->setTime(18, 0),
            ],
            'night' => [
                $date->copy()->setTime(18, 0),
                $date->copy()->addDay()->setTime(6, 0),
            ],
            default => throw new \Exception("Invalid shift type"),
        };
    }

    public function getActiveShift(?Carbon $at = null): ?ShiftInstance
    {
        $now = $at ?? Carbon::now();

        return ShiftInstance::where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->where('status', 'open')
            ->first();
    }

    public function getActiveShiftForGuard(int $guardId, ?Carbon $at = null): ?ShiftInstance
    {
        $now = $at ?? Carbon::now();

        return ShiftInstance::where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->where('status', 'open')
            ->whereHas('assignments', function ($q) use ($guardId) {
                $q->where('guard_id', $guardId);
            })
            ->with('schedule')
            ->first();
    }

    public function currentShiftKey(?Carbon $at = null): array
    {
        $now = $at ?? Carbon::now('Africa/Dar_es_Salaam');
        $hour = (int) $now->format('H');

        if ($hour >= 6 && $hour < 18) {
            [$startsAt, $endsAt] = $this->resolveShiftWindow($now, 'morning');

            return [
                'date'      => $now->toDateString(),
                'shift'     => 'morning',
                'starts_at' => $startsAt,
                'ends_at'   => $endsAt,
            ];
        }

        if ($hour >= 18) {
            [$startsAt, $endsAt] = $this->resolveShiftWindow($now, 'night');

            return [
                'date'      => $now->toDateString(),
                'shift'     => 'night',
                'starts_at' => $startsAt,
                'ends_at'   => $endsAt,
            ];
        }

        $previousDay = $now->copy()->subDay();
        [$startsAt, $endsAt] = $this->resolveShiftWindow($previousDay, 'night');

        return [
            'date'      => $previousDay->toDateString(),
            'shift'     => 'night',
            'starts_at' => $startsAt,
            'ends_at'   => $endsAt,
        ];
    }
}