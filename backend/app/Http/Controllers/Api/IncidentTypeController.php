<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncidentType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IncidentTypeController extends Controller
{
    /**
     * GET /api/incident-types
     * Everyone sees only active types (for the report form dropdown).
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => IncidentType::where('is_active', true)->get(),
        ]);
    }

    /**
     * POST /api/incident-types
     * Admin only — enforce with middleware/permission in the route.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:incident_types,name',
            'description' => 'nullable|string',
        ]);

        $type = IncidentType::create($request->only(['name', 'description']));

        return response()->json(['data' => $type], 201);
    }

    /**
     * PUT /api/incident-types/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $type = IncidentType::find($id);

        if (!$type) {
            return response()->json(['message' => 'Incident type not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:incident_types,name,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $type->update($request->only(['name', 'description', 'is_active']));

        return response()->json(['data' => $type]);
    }
}