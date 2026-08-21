<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RosterAssignment;
use App\Services\RosterEngineService;

class RosterController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
        ]);

        $startDate = $request->input('start_date')
            ?? now('Africa/Dar_es_Salaam')->startOfWeek()->toDateString();

        $result = app(RosterEngineService::class)
            ->generateWeeklyRoster($startDate, $request->user()?->id);

        return response()->json([
            'message' => 'Roster generated successfully',
            'data' => $result
        ]);
    }

    public function assignDouble(Request $request)
    {
        $request->validate([
            'guard_id' => 'required|integer|exists:guards,id',
            'post_id'  => 'required|integer|exists:posts,id',
            'date'     => 'required|date',
            'shift'    => 'required|in:morning,night',
        ]);

        $result = app(RosterEngineService::class)->assignDoubleShift(
            guardId: $request->guard_id,
            postId: $request->post_id,
            date: $request->date,
            shift: $request->shift,
            confirmedBy: $request->user()->id
        );

        return response()->json(
            ['message' => $result['message'] ?? null, 'data' => $result['data'] ?? null],
            $result['success'] ? 201 : 422
        );
    }

    /**
     * GET /roster
     * List roster assignments, most recent first, with guard/site/post loaded.
     */
    public function index(Request $request)
    {
        $request->validate([
            'guard_id'   => 'nullable|integer|exists:guards,id',
            'site_id'    => 'nullable|integer|exists:sites,id',
            'post_id'    => 'nullable|integer|exists:posts,id',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = RosterAssignment::with(['assignedGuard', 'site', 'post'])
            ->orderBy('date')
            ->orderBy('site_id')
            ->orderBy('shift');

        if ($request->guard_id) {
            $query->where('guard_id', $request->guard_id);
        }

        if ($request->site_id) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->post_id) {
            $query->where('post_id', $request->post_id);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
            return response()->json(['data' => $query->get()]);
        }

        return response()->json(['data' => $query->orderByDesc('date')->paginate(30)]);
    }

    /**
     * GET /roster/{date}
     * All assignments for one specific date, across all sites.
     */
    public function showDate(string $date)
    {
        $assignments = RosterAssignment::with(['assignedGuard', 'site', 'post'])
            ->where('date', $date)
            ->orderBy('site_id')
            ->orderBy('shift')
            ->get();

        return response()->json(['data' => $assignments]);
    }

    /**
     * DELETE /roster/{id}
     * Remove a single roster assignment.
     */
    public function destroy(int $id)
    {
        $assignment = RosterAssignment::find($id);

        if (!$assignment) {
            return response()->json(['message' => 'Roster assignment not found'], 404);
        }

        $assignment->delete();

        return response()->json(['message' => 'Roster assignment deleted']);
    }
}