<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guard;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    /**
     * POST /api/attendance/check-in
     */
    public function checkIn(Request $request): JsonResponse
    {
        $request->validate([
            'guard_id' => 'required|integer|exists:guards,id',
            'site_id'  => 'nullable|integer|exists:sites,id',
        ]);

        $guard = Guard::findOrFail($request->guard_id);

        $result = $this->attendanceService->checkIn(
            $guard,
            $request->site_id,
            $request->user()->id
        );

        return response()->json(
            ['message' => $result['message'] ?? null, 'data' => $result['data'] ?? null],
            $result['success'] ? 200 : 422
        );
    }

    /**
     * POST /api/attendance/check-out
     */
    public function checkOut(Request $request): JsonResponse
    {
        $request->validate([
            'guard_id' => 'required|integer|exists:guards,id',
            'roster_assignment_id' => 'nullable|integer|exists:roster_assignments,id',
        ]);

        $guard = Guard::findOrFail($request->guard_id);

        $result = $this->attendanceService->checkOut(
            $guard,
            $request->user()->id,
            $request->roster_assignment_id
        );

        return response()->json(
            ['message' => $result['message'] ?? null, 'data' => $result['data'] ?? null],
            $result['success'] ? 200 : 422
        );
    }

    /**
     * GET /api/attendance/today
     */
    public function today(): JsonResponse
    {
        $summary = $this->attendanceService->todaySummary();

        return response()->json(['data' => $summary]);
    }

    /**
     * GET /api/attendance/guard/{guardId}
     */
    public function guardHistory(Request $request, int $guardId): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $query = Attendance::with(['site', 'rosterAssignment'])
            ->where('guard_id', $guardId)
            ->orderByDesc('check_in_at');

        if ($request->from) {
            $query->whereDate('check_in_at', '>=', $request->from);
        }

        if ($request->to) {
            $query->whereDate('check_in_at', '<=', $request->to);
        }

        return response()->json(['data' => $query->paginate(30)]);
    }

    /**
     * GET /api/attendance/site/{siteId}
     */
    public function siteAttendance(Request $request, int $siteId): JsonResponse
    {
        $request->validate([
            'date'  => 'nullable|date',
            'shift' => 'nullable|in:morning,night',
        ]);

        $date = $request->date ?? today()->toDateString();

        $records = Attendance::with(['securityGuard', 'rosterAssignment'])
            ->where('site_id', $siteId)
            ->whereDate('check_in_at', $date)
            ->when($request->shift, function ($q) use ($request) {
                $q->whereHas('rosterAssignment', function ($sq) use ($request) {
                    $sq->where('shift', $request->shift);
                });
            })
            ->get();

        return response()->json(['data' => $records]);
    }
}