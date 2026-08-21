<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Post;
use App\Models\RosterAssignment;
use App\Models\Site;
use App\Services\ShiftEngineService;

class SupervisorDashboardController extends Controller
{
    public function index()
    {
        $shiftKey = app(ShiftEngineService::class)->currentShiftKey();
        $requiredField = $shiftKey['shift'] === 'morning'
            ? 'morning_guards_required'
            : 'night_guards_required';

        // Pull real, distinct zones from active sites instead of a
        // hardcoded list — any new zone is automatically included.
        $zones = Site::where('status', 'active')
            ->whereNotNull('zone')
            ->distinct()
            ->pluck('zone');

        $response = [];

        foreach ($zones as $zone) {
            $sites = Site::where('zone', $zone)->where('status', 'active')->get();
            $siteIds = $sites->pluck('id');

            $rosterIds = RosterAssignment::whereIn('site_id', $siteIds)
                ->where('date', $shiftKey['date'])
                ->where('shift', $shiftKey['shift'])
                ->pluck('id');

            $attendance = Attendance::with(['securityGuard', 'site'])
                ->whereIn('roster_assignment_id', $rosterIds)
                ->get();

            $required = Post::whereIn('site_id', $siteIds)
                ->where('status', 'active')
                ->sum($requiredField);

            $present = $attendance->whereNull('check_out_at')->count();

            $response[$zone] = [
                'sites' => $sites->count(),
                'required_guards' => $required,
                'present_guards' => $present,
                'missing_guards' => max($required - $present, 0),
                'late_guards' => $attendance->where('status', 'late')->count(),
                'on_duty' => $attendance->whereNull('check_out_at')->values(),
            ];
        }

        return response()->json([
            'shift' => $shiftKey['shift'],
            'date' => $shiftKey['date'],
            'zones' => $response,
        ]);
    }
}