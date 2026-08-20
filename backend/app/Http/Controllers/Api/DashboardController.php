<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\RosterAssignment;
use App\Services\ShiftEngineService;
use App\Services\AttendanceService;

class DashboardController extends Controller
{
    public function liveShift()
    {
        $shiftKey = app(ShiftEngineService::class)->currentShiftKey();
        $requiredField = $shiftKey['shift'] === 'morning' ? 'morning_guards_required' : 'night_guards_required';

        $activeSites = \App\Models\Site::where('status', 'active')->get();

        $expected = RosterAssignment::with(['site'])
            ->where('date', $shiftKey['date'])
            ->where('shift', $shiftKey['shift'])
            ->get();

        $onDuty = Attendance::with(['securityGuard', 'site'])
            ->whereIn('roster_assignment_id', $expected->pluck('id'))
            ->whereNull('check_out_at')
            ->get();

        $late = Attendance::with(['securityGuard', 'site'])
            ->whereIn('roster_assignment_id', $expected->pluck('id'))
            ->where('status', 'late')
            ->get();

        // Base the summary on EVERY active site, not just sites that
        // already have assignments — an unstaffed site is a real shortage,
        // not something that should silently vanish from the dashboard.
        $sites = $activeSites->map(function ($site) use ($expected, $onDuty, $requiredField) {
            $required = $site->{$requiredField} ?? 0;
            $present = $onDuty->where('site_id', $site->id)->count();

            return [
                'site_id' => $site->id,
                'site_name' => $site->name,
                'required_guards' => $required,
                'present_guards' => $present,
                'missing_guards' => max($required - $present, 0),
            ];
        });

        return response()->json([
            'shift' => $shiftKey['shift'],
            'date' => $shiftKey['date'],
            'on_duty' => $onDuty,
            'late' => $late,
            'site_summary' => $sites->values(),
        ]);
    }

    /**
     * GET /api/dashboard/admin
     * Company-wide totals for today (from AttendanceService) plus a
     * per-site breakdown covering the WHOLE day (both shifts combined) —
     * distinct from liveShift(), which only covers the current shift.
     */
    public function adminSummary()
    {
        $companySummary = app(AttendanceService::class)->todaySummary();

        $today = $companySummary['date'];

        $rosterToday = RosterAssignment::with('site')
            ->where('date', $today)
            ->get();

        $attendanceToday = Attendance::whereIn('roster_assignment_id', $rosterToday->pluck('id'))->get();

        $siteSummary = $rosterToday->groupBy('site_id')->map(function ($assignments) use ($attendanceToday) {
            $rosterIds = $assignments->pluck('id');
            $siteAttendance = $attendanceToday->whereIn('roster_assignment_id', $rosterIds);
            $checkedInGuardIds = $siteAttendance->pluck('guard_id')->unique();

            return [
                'site_id' => $assignments->first()->site_id,
                'site_name' => $assignments->first()->site->name ?? null,
                'required_guards' => $assignments->count(),
                'checked_in' => $checkedInGuardIds->count(),
                'late' => $siteAttendance->where('status', 'late')->count(),
                'missing' => $assignments->count() - $checkedInGuardIds->count(),
            ];
        })->values();

        return response()->json([
            'company_summary' => $companySummary,
            'site_summary' => $siteSummary,
        ]);
    }
}