<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MoneyRequest;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MoneyRequestController extends Controller
{
    public function __construct(private ApprovalWorkflowService $approvalService) {}

    /*
    |---------------------------------------
    | SUBMIT A MONEY REQUEST
    | admin, manager
    |
    | Creates the MoneyRequest record and immediately starts its
    | approval chain via the generic engine, in one transaction, so
    | a request can never exist without a matching approval flow.
    |---------------------------------------
    */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'amount'        => 'required|numeric|min:0.01',
            'reason'        => 'required|string',
            'site_id'       => 'nullable|exists:sites,id',
            'contract_id'   => 'nullable|exists:contracts,id',
            'category'      => 'nullable|string',
            'attachment'    => 'nullable|file|max:5120',
        ]);

        $result = DB::transaction(function () use ($validated, $request) {
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('money-requests', 'public');
            }

            $moneyRequest = MoneyRequest::create([
                'department_id'   => $validated['department_id'],
                'amount'          => $validated['amount'],
                'reason'          => $validated['reason'],
                'site_id'         => $validated['site_id'] ?? null,
                'contract_id'     => $validated['contract_id'] ?? null,
                'category'        => $validated['category'] ?? null,
                'attachment_path' => $attachmentPath,
                'requested_by'    => Auth::id(),
            ]);

            $approval = $this->approvalService->submit(
                'money_request',
                $moneyRequest->id,
                'money_request',
                Auth::id(),
                (float) $validated['amount']
            );

            return ['moneyRequest' => $moneyRequest, 'approval' => $approval];
        });

        if (!$result['approval']['success']) {
            return response()->json(['message' => $result['approval']['message']], 422);
        }

        return response()->json([
            'message' => 'Money request submitted for approval.',
            'data' => [
                'money_request' => $result['moneyRequest'],
                'approval_request' => $result['approval']['data'],
            ],
        ], 201);
    }

    /*
    |---------------------------------------
    | LIST MONEY REQUESTS
    | admin sees all; everyone else sees only their own submissions
    |---------------------------------------
    */
    public function index(Request $request): JsonResponse
    {
        $query = MoneyRequest::with(['department', 'site', 'requester', 'approvalRequest'])
            ->latest();

        if (!$request->user()->hasRole('admin')) {
            $query->where('requested_by', $request->user()->id);
        }

        return response()->json($query->get());
    }
}