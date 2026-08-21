<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /*
    |---------------------------------------
    | GET ALL DEPARTMENTS
    | Admin + Supervisor
    |---------------------------------------
    */
    public function index()
    {
        return response()->json(
            Department::latest()->get()
        );
    }

    /*
    |---------------------------------------
    | CREATE DEPARTMENT
    | Admin ONLY
    |---------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:departments',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $department = Department::create([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Department created successfully',
            'data' => $department
        ]);
    }

    /*
    |---------------------------------------
    | SHOW SINGLE DEPARTMENT
    | Admin + Supervisor
    |---------------------------------------
    */
    public function show($id)
    {
        $department = Department::with('employees')->findOrFail($id);
        return response()->json($department);
    }

    /*
    |---------------------------------------
    | UPDATE DEPARTMENT
    | Admin ONLY
    |---------------------------------------
    */
    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:departments,name,' . $id,
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $department->update($validated);

        return response()->json([
            'message' => 'Department updated successfully',
            'data' => $department
        ]);
    }

    /*
    |---------------------------------------
    | DELETE DEPARTMENT
    | Admin ONLY
    |---------------------------------------
    */
    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        $department->delete();
        return response()->json([
            'message' => 'Department deleted successfully'
        ]);
    }
}