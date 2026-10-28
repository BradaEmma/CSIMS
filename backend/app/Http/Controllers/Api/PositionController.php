<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PositionController extends Controller
{
    // The guard module creates employee records with this exact position and
    // department, so it cannot be renamed, moved or deleted from the screen.
    private const PROTECTED_POSITIONS = ['Guard'];

    /*
    |---------------------------------------
    | GET APPROVED POSITIONS
    | Anyone with employees.view (used by the employee form)
    |---------------------------------------
    */
    public function index()
    {
        return response()->json(
            Position::with('department:id,name')->orderBy('name')->get()
        );
    }

    /*
    |---------------------------------------
    | CREATE POSITION
    | Admin ONLY
    |---------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:positions,name',
            'department_id' => 'required|exists:departments,id',
        ]);

        $position = Position::create($validated);

        return response()->json([
            'message' => 'Position created successfully',
            'data' => $position->load('department:id,name'),
        ]);
    }

    /*
    |---------------------------------------
    | UPDATE POSITION (rename and/or move to another department)
    | Admin ONLY. Employees holding the position are updated in the
    | same transaction so the list and the employees never drift apart.
    |---------------------------------------
    */
    public function update(Request $request, $id)
    {
        $position = Position::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:positions,name,' . $id,
            'department_id' => 'required|exists:departments,id',
        ]);

        $changed = $validated['name'] !== $position->name
            || (int) $validated['department_id'] !== (int) $position->department_id;

        if ($changed && in_array($position->name, self::PROTECTED_POSITIONS, true)) {
            return response()->json([
                'message' => "The '{$position->name}' position is used by the guard system and cannot be renamed or moved.",
            ], 422);
        }

        $employeesUpdated = 0;

        DB::transaction(function () use ($position, $validated, &$employeesUpdated) {
            $oldName = $position->name;

            $position->update($validated);

            $employeesUpdated = Employee::where('position', $oldName)->update([
                'position' => $position->name,
                'department_id' => $position->department_id,
            ]);
        });

        return response()->json([
            'message' => 'Position updated successfully',
            'employees_updated' => $employeesUpdated,
            'data' => $position->load('department:id,name'),
        ]);
    }

    /*
    |---------------------------------------
    | DELETE POSITION
    | Admin ONLY. Blocked while any employee holds it.
    |---------------------------------------
    */
    public function destroy($id)
    {
        $position = Position::findOrFail($id);

        if (in_array($position->name, self::PROTECTED_POSITIONS, true)) {
            return response()->json([
                'message' => "The '{$position->name}' position is used by the guard system and cannot be deleted.",
            ], 422);
        }

        $holders = Employee::where('position', $position->name)->count();

        if ($holders > 0) {
            return response()->json([
                'message' => "Cannot delete: {$holders} employee(s) hold this position. Move them to another position first.",
            ], 422);
        }

        $position->delete();

        return response()->json(['message' => 'Position deleted successfully']);
    }
}