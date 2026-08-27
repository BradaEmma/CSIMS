<?php

namespace App\Services;

use Carbon\Carbon;

class ShiftEngineService
{
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