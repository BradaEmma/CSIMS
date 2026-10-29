<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\DepartmentScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Photo and documents for employee records.
 *
 * Separate from DocumentController on purpose: that controller (guards and
 * contracts) stays role-gated for admin and supervisor, while these routes
 * use the employees.view / employees.manage permissions that HR already holds.
 */
class EmployeeDocumentController extends Controller
{
    private const TYPES = ['passport_photo', 'national_id', 'employment_contract', 'cv', 'other'];

    private function findEmployee(Request $request, $id): Employee
    {
        return DepartmentScopeService::apply(Employee::query(), $request->user())->findOrFail($id);
    }

    /**
     * GET /employees/{id}/documents?type=national_id
     */
    public function index(Request $request, $id): JsonResponse
    {
        $employee = $this->findEmployee($request, $id);

        $request->validate([
            'type' => 'nullable|in:' . implode(',', self::TYPES),
        ]);

        $query = $employee->documents()->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return response()->json(['data' => $query->get()]);
    }

    /**
     * POST /employees/{id}/documents   (file + type)
     */
    public function store(Request $request, $id): JsonResponse
    {
        $employee = $this->findEmployee($request, $id);

        $fileRule = $request->input('type') === 'passport_photo'
            ? 'required|file|mimes:jpg,jpeg,png|max:10240'
            : 'required|file|mimes:pdf,jpg,jpeg,png|max:10240';

        $request->validate([
            'file' => $fileRule,
            'type' => 'required|in:' . implode(',', self::TYPES),
        ]);

        $path = $request->file('file')->store('documents', 'public');

        $document = $employee->documents()->create([
            'type'              => $request->type,
            'file_path'         => $path,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'uploaded_by'       => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'data' => $document,
        ], 201);
    }

    /**
     * DELETE /employees/{id}/documents/{docId}
     */
    public function destroy(Request $request, $id, $docId): JsonResponse
    {
        $employee = $this->findEmployee($request, $id);

        // Looked up through the employee, so a document of another record cannot be deleted here.
        $document = $employee->documents()->find($docId);

        if (!$document) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return response()->json(['message' => 'Document deleted successfully']);
    }
}