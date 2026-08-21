<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    /*
    |---------------------------------------
    | GET ALL EMPLOYEES
    | Admin + Supervisor
    |---------------------------------------
    */
    public function index()
    {
        return response()->json(
            Employee::with(['department', 'creator'])->latest()->get()
        );
    }

    /*
    |---------------------------------------
    | CREATE EMPLOYEE
    | Admin ONLY
    |---------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string|unique:employees',
            'email' => 'nullable|email|unique:employees',
            'national_id' => 'nullable|string|unique:employees',
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'hire_date' => 'nullable|date',
        ]);

        $employee = Employee::create([
            ...$validated,
            'status' => $validated['status'] ?? 'active',
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Employee created successfully',
            'data' => $employee
        ]);
    }

    /*
    |---------------------------------------
    | SHOW SINGLE EMPLOYEE
    | Admin + Supervisor
    |---------------------------------------
    */
    public function show($id)
    {
        $employee = Employee::with(['department', 'creator', 'guardProfile'])->findOrFail($id);
        return response()->json($employee);
    }

    /*
    |---------------------------------------
    | UPDATE EMPLOYEE
    | Admin ONLY
    |---------------------------------------
    */
    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string',
            'phone' => 'sometimes|string|unique:employees,phone,' . $id,
            'email' => 'sometimes|nullable|email|unique:employees,email,' . $id,
            'national_id' => 'sometimes|nullable|string|unique:employees,national_id,' . $id,
            'department_id' => 'sometimes|nullable|exists:departments,id',
            'position' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:active,inactive',
            'hire_date' => 'sometimes|nullable|date',
        ]);

        $employee->update($validated);

        return response()->json([
            'message' => 'Employee updated successfully',
            'data' => $employee
        ]);
    }

    /*
    |---------------------------------------
    | DELETE EMPLOYEE
    | Admin ONLY
    |---------------------------------------
    */
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();
        return response()->json([
            'message' => 'Employee deleted successfully'
        ]);
    }
}