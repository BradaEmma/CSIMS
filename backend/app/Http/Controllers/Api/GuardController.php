<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guard;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GuardController extends Controller
{
    /*
    |---------------------------------------
    | GET ALL GUARDS
    | Admin + Supervisor
    |---------------------------------------
    */
    public function index()
    {
        return response()->json(
            Guard::with(['site', 'creator', 'documents'])->latest()->get()
        );
    }

    /*
    |---------------------------------------
    | CREATE GUARD
    | Admin ONLY
    |
    | Also creates the linked Employee record automatically, so every
    | new guard has a proper universal identity from the start. Wrapped
    | in a transaction so a failure on either insert leaves nothing
    | orphaned. Existing guards created before this change are NOT
    | affected/backfilled by this — that's a separate future task.
    |---------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string|unique:guards',
            'national_id' => 'nullable|string|unique:guards',
            'shift_type' => 'required|in:morning,night,either',
            'site_id' => 'nullable|exists:sites,id',
            'daily_rate' => 'required|numeric|min:0',
            'nssf_applicable' => 'nullable|boolean',
            'paye_applicable' => 'nullable|boolean',
        ]);

        $guard = DB::transaction(function () use ($validated) {
            $employee = Employee::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'national_id' => $validated['national_id'] ?? null,
                'position' => 'Guard',
                'status' => 'active',
                'created_by' => Auth::id(),
            ]);

            return Guard::create([
                ...$validated,
                'status' => 'active',
                'created_by' => Auth::id(),
                'employee_id' => $employee->id,
            ]);
        });

        return response()->json([
            'message' => 'Guard created successfully',
            'data' => $guard
        ]);
    }

    /*
    |---------------------------------------
    | SHOW SINGLE GUARD
    | Admin + Supervisor
    |---------------------------------------
    */
    public function show($id)
    {
        $guard = Guard::with(['site', 'assignments'])->findOrFail($id);
        return response()->json($guard);
    }

    /*
    |---------------------------------------
    | UPDATE GUARD
    | Admin ONLY
    |---------------------------------------
    */
    public function update(Request $request, $id)
    {
        $guard = Guard::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string',
            'phone' => 'sometimes|string|unique:guards,phone,' . $id,
            'national_id' => 'sometimes|string|unique:guards,national_id,' . $id,
            'shift_type' => 'sometimes|in:morning,night,either',
            'status' => 'sometimes|in:active,inactive',
            'site_id' => 'sometimes|exists:sites,id',
            'daily_rate' => 'sometimes|numeric|min:0',
            'nssf_applicable' => 'sometimes|boolean',
            'paye_applicable' => 'sometimes|boolean',
        ]);

        $guard->update($validated);

        return response()->json([
            'message' => 'Guard updated successfully',
            'data' => $guard
        ]);
    }

    /*
    |---------------------------------------
    | DELETE GUARD
    | Admin ONLY
    |---------------------------------------
    */
    public function destroy($id)
    {
        $guard = Guard::findOrFail($id);
        $guard->delete();
        return response()->json([
            'message' => 'Guard deleted successfully'
        ]);
    }
}