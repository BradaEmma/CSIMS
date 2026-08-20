<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DocumentController extends Controller
{
    /**
     * POST /documents
     * Generic upload — works for any documentable entity.
     * Expects: documentable_type (e.g. "guard", "contract"),
     * documentable_id, file, and optional type label.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'documentable_type' => 'required|in:guard,contract',
            'documentable_id'   => 'required|integer',
            'file'              => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB
            'type'              => 'nullable|string',
        ]);

        $modelMap = [
            'guard'    => \App\Models\Guard::class,
            'contract' => \App\Models\Contract::class,
        ];

        $modelClass = $modelMap[$request->documentable_type];
        $entity = $modelClass::find($request->documentable_id);

        if (!$entity) {
            return response()->json(['message' => ucfirst($request->documentable_type) . ' not found'], 404);
        }

        $path = $request->file('file')->store('documents', 'public');

        $document = $entity->documents()->create([
            'type'               => $request->type,
            'file_path'          => $path,
            'original_filename'  => $request->file('file')->getClientOriginalName(),
            'uploaded_by'        => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'data' => $document,
        ], 201);
    }

    /**
     * GET /documents?documentable_type=guard&documentable_id=1
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'documentable_type' => 'required|in:guard,contract',
            'documentable_id'   => 'required|integer',
            'type'              => 'nullable|string',
        ]);

        $modelMap = [
            'guard'    => \App\Models\Guard::class,
            'contract' => \App\Models\Contract::class,
        ];

        $query = Document::where('documentable_type', $modelMap[$request->documentable_type])
            ->where('documentable_id', $request->documentable_id)
            ->latest();

        if ($request->type) {
            $query->where('type', $request->type);
        }

        return response()->json(['data' => $query->get()]);
    }

    /**
     * DELETE /documents/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        \Illuminate\Support\Facades\Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return response()->json(['message' => 'Document deleted successfully']);
    }
}