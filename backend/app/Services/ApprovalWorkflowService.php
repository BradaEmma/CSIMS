<?php

namespace App\Services;

use App\Models\ApprovalAction;
use App\Models\ApprovalRequest;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowService
{
    /**
     * Explicit allowlist of approvable types.
     * Mirrors the Document model's documentable_type convention —
     * short string keys, not raw class paths stored in the DB.
     */
    protected array $approvableMap = [
        'payroll_record' => \App\Models\PayrollRecord::class,
    ];

    /**
     * Submit a new approval request for a given entity.
     */
    public function submit(string $approvableType, int $approvableId, string $module, int $submittedBy, ?float $amount = null): array
    {
        if (!array_key_exists($approvableType, $this->approvableMap)) {
            return ['success' => false, 'message' => 'Invalid approvable type.'];
        }

        $workflow = ApprovalWorkflow::where('module', $module)
            ->where('is_active', true)
            ->first();

        if (!$workflow) {
            return ['success' => false, 'message' => "No active approval workflow found for module '{$module}'."];
        }

        $firstLevel = $workflow->levels()
            ->where(function ($q) use ($amount) {
                $q->whereNull('min_amount')->orWhere('min_amount', '<=', $amount ?? 0);
            })
            ->where(function ($q) use ($amount) {
                $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount ?? 0);
            })
            ->orderBy('sequence')
            ->first();

        if (!$firstLevel) {
            return ['success' => false, 'message' => 'No matching workflow level found for this request.'];
        }

        $request = ApprovalRequest::create([
            'approvable_type' => $approvableType,
            'approvable_id' => $approvableId,
            'approval_workflow_id' => $workflow->id,
            'current_level' => $firstLevel->sequence,
            'amount' => $amount,
            'status' => 'pending',
            'submitted_by' => $submittedBy,
        ]);

        return ['success' => true, 'message' => 'Approval request submitted.', 'data' => $request];
    }

    /**
     * Approve the current level of a request. Advances to next matching
     * level (by amount range), or marks fully approved if no further
     * level applies.
     */
    public function approve(int $requestId, int $actingUserId, ?string $comment = null): array
    {
        return DB::transaction(function () use ($requestId, $actingUserId, $comment) {
            $request = ApprovalRequest::with('workflow.levels')->find($requestId);

            if (!$request) {
                return ['success' => false, 'message' => 'Approval request not found.'];
            }

            if ($request->status !== 'pending') {
                return ['success' => false, 'message' => "Request is not pending (current status: {$request->status})."];
            }

            // Separation of duties: submitter cannot approve their own request.
            if ($request->submitted_by === $actingUserId) {
                return ['success' => false, 'message' => 'You cannot approve a request you submitted.'];
            }

            $currentLevel = $request->workflow->levels->firstWhere('sequence', $request->current_level);

            if (!$currentLevel) {
                return ['success' => false, 'message' => 'Current approval level not found on workflow.'];
            }

            $actingUser = User::find($actingUserId);

            if (!$actingUser || !$actingUser->hasRole($currentLevel->approver_role)) {
                return ['success' => false, 'message' => 'You are not authorized to approve this level.'];
            }

            ApprovalAction::create([
                'approval_request_id' => $request->id,
                'approval_workflow_level_id' => $currentLevel->id,
                'user_id' => $actingUserId,
                'action' => 'approve',
                'comment' => $comment,
            ]);

            $nextLevel = $request->workflow->levels
                ->where('sequence', '>', $currentLevel->sequence)
                ->filter(function ($lvl) use ($request) {
                    $withinMin = is_null($lvl->min_amount) || $request->amount >= $lvl->min_amount;
                    $withinMax = is_null($lvl->max_amount) || $request->amount <= $lvl->max_amount;
                    return $withinMin && $withinMax;
                })
                ->sortBy('sequence')
                ->first();

            if ($nextLevel) {
                $request->update(['current_level' => $nextLevel->sequence]);
                return ['success' => true, 'message' => 'Approved. Advanced to next level.', 'data' => $request->fresh()];
            }

            $request->update(['status' => 'approved']);
            return ['success' => true, 'message' => 'Request fully approved.', 'data' => $request->fresh()];
        });
    }

    /**
     * Reject a request outright. Terminal status.
     */
    public function reject(int $requestId, int $actingUserId, string $comment): array
    {
        return $this->terminalAction($requestId, $actingUserId, $comment, 'reject', 'rejected');
    }

    /**
     * Return a request to the submitter for changes. Terminal for this
     * submission — resubmission creates a fresh approval flow via submit().
     */
    public function returnRequest(int $requestId, int $actingUserId, string $comment): array
    {
        return $this->terminalAction($requestId, $actingUserId, $comment, 'return', 'returned');
    }

    /**
     * Cancel a request (by the submitter, or an admin).
     */
    public function cancel(int $requestId, int $actingUserId, ?string $comment = null): array
    {
        $request = ApprovalRequest::find($requestId);

        if (!$request) {
            return ['success' => false, 'message' => 'Approval request not found.'];
        }

        if ($request->status !== 'pending') {
            return ['success' => false, 'message' => "Request is not pending (current status: {$request->status})."];
        }

        ApprovalAction::create([
            'approval_request_id' => $request->id,
            'approval_workflow_level_id' => null,
            'user_id' => $actingUserId,
            'action' => 'cancel',
            'comment' => $comment,
        ]);

        $request->update(['status' => 'cancelled']);

        return ['success' => true, 'message' => 'Request cancelled.', 'data' => $request->fresh()];
    }

    /**
     * Get all pending requests where the given user is an eligible approver
     * at the request's current level.
     */
    public function getPendingForUser(int $userId): array
    {
        $user = User::find($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        $userRoles = $user->getRoleNames();

        $requests = ApprovalRequest::with(['workflow.levels', 'submitter'])
            ->where('status', 'pending')
            ->get()
            ->filter(function (ApprovalRequest $request) use ($userRoles) {
                $level = $request->workflow->levels->firstWhere('sequence', $request->current_level);
                return $level && $userRoles->contains($level->approver_role);
            })
            ->values();

        return ['success' => true, 'data' => $requests];
    }

    protected function terminalAction(int $requestId, int $actingUserId, string $comment, string $action, string $newStatus): array
    {
        return DB::transaction(function () use ($requestId, $actingUserId, $comment, $action, $newStatus) {
            $request = ApprovalRequest::with('workflow.levels')->find($requestId);

            if (!$request) {
                return ['success' => false, 'message' => 'Approval request not found.'];
            }

            if ($request->status !== 'pending') {
                return ['success' => false, 'message' => "Request is not pending (current status: {$request->status})."];
            }

            $currentLevel = $request->workflow->levels->firstWhere('sequence', $request->current_level);
            $actingUser = User::find($actingUserId);

            if (!$currentLevel || !$actingUser || !$actingUser->hasRole($currentLevel->approver_role)) {
                return ['success' => false, 'message' => 'You are not authorized to act on this level.'];
            }

            ApprovalAction::create([
                'approval_request_id' => $request->id,
                'approval_workflow_level_id' => $currentLevel->id,
                'user_id' => $actingUserId,
                'action' => $action,
                'comment' => $comment,
            ]);

            $request->update(['status' => $newStatus]);

            return ['success' => true, 'message' => ucfirst($newStatus) . '.', 'data' => $request->fresh()];
        });
    }
}