<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApprovalController extends Controller
{
    public function __construct(private ApprovalWorkflowService $approvalService) {}

    /**
     * POST /api/v1/approvals
     */
    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'approvable_type' => 'required|string',
            'approvable_id'   => 'required|integer',
            'module'          => 'required|string',
            'amount'          => 'nullable|numeric',
        ]);

        $result = $this->approvalService->submit(
            $request->approvable_type,
            $request->approvable_id,
            $request->module,
            $request->user()->id,
            $request->amount
        );

        return response()->json(
            ['message' => $result['message'] ?? null, 'data' => $result['data'] ?? null],
            $result['success'] ? 201 : 422
        );
    }

    /**
     * POST /api/v1/approvals/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'comment' => 'nullable|string',
        ]);

        $result = $this->approvalService->approve($id, $request->user()->id, $request->comment);

        return response()->json(
            ['message' => $result['message'] ?? null, 'data' => $result['data'] ?? null],
            $result['success'] ? 200 : 422
        );
    }

    /**
     * POST /api/v1/approvals/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'comment' => 'required|string',
        ]);

        $result = $this->approvalService->reject($id, $request->user()->id, $request->comment);

        return response()->json(
            ['message' => $result['message'] ?? null, 'data' => $result['data'] ?? null],
            $result['success'] ? 200 : 422
        );
    }

    /**
     * POST /api/v1/approvals/{id}/return
     */
    public function return(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'comment' => 'required|string',
        ]);

        $result = $this->approvalService->returnRequest($id, $request->user()->id, $request->comment);

        return response()->json(
            ['message' => $result['message'] ?? null, 'data' => $result['data'] ?? null],
            $result['success'] ? 200 : 422
        );
    }

    /**
     * POST /api/v1/approvals/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'comment' => 'nullable|string',
        ]);

        $result = $this->approvalService->cancel($id, $request->user()->id, $request->comment);

        return response()->json(
            ['message' => $result['message'] ?? null, 'data' => $result['data'] ?? null],
            $result['success'] ? 200 : 422
        );
    }

    /**
     * GET /api/v1/approvals/pending
     * Requests where the current user is an eligible approver at the
     * request's current level.
     */
    public function pending(Request $request): JsonResponse
    {
        $result = $this->approvalService->getPendingForUser($request->user()->id);

        return response()->json(
            ['message' => $result['message'] ?? null, 'data' => $result['data'] ?? null],
            $result['success'] ? 200 : 422
        );
    }

    /**
     * GET /api/v1/approvals/{id}
     * Single request detail + full action history.
     */
    public function show(int $id): JsonResponse
    {
        $approvalRequest = ApprovalRequest::with(['workflow.levels', 'submitter', 'actions.user'])
            ->find($id);

        if (!$approvalRequest) {
            return response()->json(['message' => 'Approval request not found'], 404);
        }

        return response()->json(['data' => $approvalRequest]);
    }

    /**
     * GET /api/v1/approvals
     * Admin-only list of all approval requests, optionally filtered by status.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected,returned,cancelled',
        ]);

        $query = ApprovalRequest::with(['workflow', 'submitter'])
            ->orderByDesc('created_at');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return response()->json(['data' => $query->paginate(30)]);
    }
}