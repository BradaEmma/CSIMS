<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    /**
     * GET /sites
     */
    public function index()
    {
        return response()->json(Site::with('posts')->latest()->get());
    }

    /**
     * POST /sites
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string',
            'zone'             => 'required|string',
            'location'         => 'nullable|string',
            'description'      => 'nullable|string',
            'required_guards'  => 'nullable|integer|min:0',
            'contract_id'      => 'nullable|exists:contracts,id',
        ]);

        $site = Site::create([
            ...$validated,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Site created successfully',
            'data' => $site
        ]);
    }

    /**
     * GET /sites/{id}
     */
    public function show($id)
    {
        $site = Site::with('posts')->findOrFail($id);
        return response()->json($site);
    }

    /**
     * PUT /sites/{id}
     */
    public function update(Request $request, $id)
    {
        $site = Site::findOrFail($id);

        $validated = $request->validate([
            'name'             => 'sometimes|string',
            'zone'             => 'sometimes|string',
            'location'         => 'sometimes|nullable|string',
            'description'      => 'sometimes|nullable|string',
            'required_guards'  => 'sometimes|integer|min:0',
            'contract_id'      => 'sometimes|nullable|exists:contracts,id',
            'status'           => 'sometimes|in:active,inactive',
        ]);

        $site->update($validated);

        return response()->json([
            'message' => 'Site updated successfully',
            'data' => $site
        ]);
    }

    /**
     * DELETE /sites/{id}
     */
    public function destroy($id)
    {
        $site = Site::findOrFail($id);
        $site->delete();

        return response()->json([
            'message' => 'Site deleted successfully'
        ]);
    }
}