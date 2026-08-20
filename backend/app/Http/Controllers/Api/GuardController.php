<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $guard = Guard::create([
            ...$validated,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

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