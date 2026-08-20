<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuardAssignment;
use App\Models\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuardAssignmentController extends Controller
{
    // LIST ASSIGNMENTS
    public function index()
    {
        return GuardAssignment::with(['assignedGuard', 'site', 'assignedBy'])->latest()->get();
    }

    // ASSIGN GUARD
    public function assign(Request $request)
    {
        $request->validate([
            'guard_id' => 'required|exists:guards,id',
            'site_id' => 'required|exists:sites,id',
            'start_date' => 'required|date',
        ]);

        // prevent double active assignment
        $exists = GuardAssignment::where('guard_id', $request->guard_id)
            ->where('status', 'active')
            ->first();

        if ($exists) {
            return response()->json([
                'message' => 'Guard already assigned'
            ], 422);
        }

        $assignment = GuardAssignment::create([
            'guard_id' => $request->guard_id,
            'site_id' => $request->site_id,
            'assigned_by' => Auth::id(),
            'start_date' => $request->start_date,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Guard assigned successfully',
            'data' => $assignment
        ]);
    }

    // END ASSIGNMENT
    public function endAssignment($id)
    {
        $assignment = GuardAssignment::findOrFail($id);

        $assignment->update([
            'status' => 'ended',
            'end_date' => now()
        ]);

        return response()->json([
            'message' => 'Assignment ended'
        ]);
    }
}