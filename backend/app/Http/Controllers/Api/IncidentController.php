<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Services\IncidentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IncidentController extends Controller
{
    public function __construct(private IncidentService $incidentService) {}

    /**
     * POST /api/incidents
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'site_id' => 'required|integer|exists:sites,id',
            'roster_assignment_id' => 'nullable|integer|exists:roster_assignments,id',
            'incident_type_id' => 'required|integer|exists:incident_types,id',
            'severity' => 'required|in:low,medium,high,critical',
            'description' => 'required|string',
            'occurred_at' => 'nullable|date',
        ]);

        $result = $this->incidentService->report($request->all(), $request->user()->id);

        return response()->json(
            ['message' => $result['message'] ?? null, 'data' => $result['data'] ?? null],
            $result['success'] ? 201 : 422
        );
    }

    /**
     * GET /api/incidents
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'site_id' => 'nullable|integer|exists:sites,id',
            'status' => 'nullable|in:open,under_review,resolved,closed',
        ]);

        $query = Incident::with(['site', 'incidentType', 'reportedBy', 'attachments'])
            ->orderByDesc('occurred_at');

        if ($request->site_id) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return response()->json(['data' => $query->paginate(30)]);
    }

    /**
     * GET /api/incidents/summary
     * Dashboard-friendly summary: this month's count + recent incidents.
     */
    public function summary(): JsonResponse
    {
        $monthCount = Incident::whereMonth('occurred_at', now()->month)
            ->whereYear('occurred_at', now()->year)
            ->count();

        $recent = Incident::with(['site', 'incidentType'])
            ->orderByDesc('occurred_at')
            ->limit(5)
            ->get();

        return response()->json([
            'data' => [
                'month_count' => $monthCount,
                'recent' => $recent,
            ],
        ]);
    }

    /**
     * GET /api/incidents/{id}
     */
    public function show(int $id): JsonResponse
    {
        $incident = Incident::with(['site', 'incidentType', 'reportedBy', 'attachments'])->find($id);

        if (!$incident) {
            return response()->json(['message' => 'Incident not found'], 404);
        }

        return response()->json(['data' => $incident]);
    }

    /**
     * POST /api/incidents/{id}/resolve
     */
    public function resolve(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'resolution_notes' => 'required|string',
        ]);

        $result = $this->incidentService->resolve($id, $request->resolution_notes);

        return response()->json(
            ['message' => $result['message'] ?? null, 'data' => $result['data'] ?? null],
            $result['success'] ? 200 : 422
        );
    }

    /**
     * PATCH /api/incidents/{id}/status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:open,under_review,resolved,closed',
        ]);

        $result = $this->incidentService->updateStatus($id, $request->status);

        return response()->json(
            ['message' => $result['message'] ?? null, 'data' => $result['data'] ?? null],
            $result['success'] ? 200 : 422
        );
    }

    /**
     * POST /api/incidents/{id}/attachments
     */
    public function uploadAttachment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|image|max:10240', // 10MB max, images only
        ]);

        $path = $request->file('file')->store('incident-attachments', 'public');

        $result = $this->incidentService->addAttachment($id, $path, $request->user()->id);

        return response()->json(
            ['message' => $result['message'] ?? null, 'data' => $result['data'] ?? null],
            $result['success'] ? 201 : 422
        );
    }
}