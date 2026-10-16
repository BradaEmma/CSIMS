<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use App\Models\PayrollDeductionType;
use App\Models\PayrollSetting;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ApprovalRequest;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payrollService) {}

    /**
     * POST /api/payroll/generate
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'guard_id' => 'required|integer|exists:guards,id',
            'period' => 'required|date_format:Y-m',
            'unpaid_days' => 'nullable|integer|min:0|max:31',
        ]);

        $result = $this->payrollService->generatePayrollForGuard(
            $request->guard_id,
            $request->period,
            $request->filled('unpaid_days') ? (int) $request->unpaid_days : null
        );

        return response()->json(
            ['message' => $result['message'] ?? null, 'data' => $result['data'] ?? null],
            $result['success'] ? 200 : 422
        );
    }

    /**
     * POST /api/payroll/generate-bulk
     */
    public function generateBulk(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'required|date_format:Y-m',
        ]);

        $result = $this->payrollService->generateBulkForPeriod($request->period);

        return response()->json(['data' => $result]);
    }

    /**
     * GET /api/payroll
     */
        public function index(Request $request): JsonResponse
    {
        $request->validate([
            'guard_id' => 'nullable|integer|exists:guards,id',
            'period' => 'nullable|date_format:Y-m',
            'status' => 'nullable|in:draft,finalized,paid',
        ]);

        $query = PayrollRecord::with(['securityGuard', 'employee'])->orderByDesc('period');

        if ($request->guard_id) {
            $query->where('guard_id', $request->guard_id);
        }

        if ($request->period) {
            $query->where('period', $request->period);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $perPage = max(1, min((int) $request->query('per_page', 30), 200));
        $paginated = $query->paginate($perPage);

        // Attach approval_status to finalized records so the frontend can
        // show "Awaiting Approval" instead of a clickable Mark Paid button
        // when a pending approval request already exists for that record.
        $finalizedIds = $paginated->getCollection()->where('status', 'finalized')->pluck('id');

        if ($finalizedIds->isNotEmpty()) {
            $latestRequests = ApprovalRequest::where('approvable_type', 'payroll_record')
                ->whereIn('approvable_id', $finalizedIds)
                ->orderByDesc('created_at')
                ->get()
                ->unique('approvable_id')
                ->keyBy('approvable_id');

            $paginated->getCollection()->transform(function ($record) use ($latestRequests) {
                $latest = $latestRequests->get($record->id);
                $record->approval_status = ($latest && $latest->status === 'pending') ? 'pending' : null;
                return $record;
            });
        }

        // Totals over the whole filtered set, independent of the current page.
        $totalsQuery = (clone $query)->reorder();

        return response()->json([
            'data' => $paginated,
            'summary' => [
                'count' => (clone $totalsQuery)->count(),
                'total_gross' => round(
                    (float) (clone $totalsQuery)->sum('gross_pay') + (float) (clone $totalsQuery)->sum('overtime_pay'),
                    2
                ),
                'total_net' => round((float) (clone $totalsQuery)->sum('net_pay'), 2),
            ],
        ]);
    }

    /**
     * GET /api/payroll/{id}
     */
    public function show(int $id): JsonResponse
    {
        $record = PayrollRecord::with('securityGuard')->find($id);

        if (!$record) {
            return response()->json(['message' => 'Payroll record not found'], 404);
        }

        return response()->json(['data' => $record]);
    }

        /**
     * GET /api/payroll/employer-cost?period=Y-m
     * Read-only employer-side cost summary, computed live from payroll records
     * (all statuses, drafts included).
     */
    public function employerCost(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'required|date_format:Y-m',
        ]);

        $records = PayrollRecord::where('period', $request->period)->get();

        $setting = fn (string $key, float $default) =>
            (float) (PayrollSetting::where('key', $key)->value('value') ?? $default);

        $nssfEmployerRate = $setting('nssf_employer_rate', 10);
        $sdlRate = $setting('sdl_rate', 3.5);
        $sdlMinEmployees = (int) $setting('sdl_min_employees', 10);
        $wcfRate = $setting('wcf_rate', 0.5);

        // Gross base = basic pay + overtime. Change here if SDL/WCF use another base.
        $totalGross = $records->sum(fn ($r) => (float) $r->gross_pay + (float) $r->overtime_pay);

        // Employer NSSF only for people who actually have NSSF deducted.
        $nssfGross = $records
            ->filter(fn ($r) => (float) $r->nssf_deduction > 0)
            ->sum(fn ($r) => (float) $r->gross_pay + (float) $r->overtime_pay);

        $headcount = \App\Models\Employee::where('status', 'active')->count();
        $sdlApplies = $headcount >= $sdlMinEmployees;

        $employerNssf = round($nssfGross * ($nssfEmployerRate / 100), 2);
        $sdl = $sdlApplies ? round($totalGross * ($sdlRate / 100), 2) : 0.0;
        $wcf = round($totalGross * ($wcfRate / 100), 2);
        $employerCost = round($employerNssf + $sdl + $wcf, 2);

        return response()->json(['data' => [
            'period' => $request->period,
            'records' => $records->count(),
            'headcount' => $headcount,
            'total_gross' => round($totalGross, 2),
            'employer_nssf' => $employerNssf,
            'sdl' => $sdl,
            'sdl_applies' => $sdlApplies,
            'wcf' => $wcf,
            'employer_cost' => $employerCost,
            'total_cost_to_company' => round($totalGross + $employerCost, 2),
            'rates' => [
                'nssf_employer' => $nssfEmployerRate,
                'sdl' => $sdlRate,
                'sdl_min_employees' => $sdlMinEmployees,
                'wcf' => $wcfRate,
            ],
        ]]);
    }

    /**
     * PATCH /api/payroll/{id}/status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:draft,finalized,paid',
        ]);


        $result = $this->payrollService->updateStatus($id, $request->status, $request->user()->id);
        return response()->json(
            ['message' => $result['message'] ?? null, 'data' => $result['data'] ?? null],
            $result['success'] ? 200 : 422
        );
    }

    /**
     * POST /api/payroll/deductions
     */
    public function addDeduction(Request $request): JsonResponse
    {
        $request->validate([
            'guard_id' => 'required_without:employee_id|nullable|integer|exists:guards,id',
            'employee_id' => 'required_without:guard_id|nullable|integer|exists:employees,id',
            'payroll_deduction_type_id' => 'required|integer|exists:payroll_deduction_types,id',
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'required|string',
            'period' => 'required|date_format:Y-m',
        ]);

        $result = $request->guard_id
            ? $this->payrollService->addDeduction(
                $request->guard_id,
                $request->payroll_deduction_type_id,
                $request->amount,
                $request->reason,
                $request->user()->id,
                $request->period
            )
            : $this->payrollService->addDeductionForEmployee(
                $request->employee_id,
                $request->payroll_deduction_type_id,
                $request->amount,
                $request->reason,
                $request->user()->id,
                $request->period
            );

        return response()->json(
            ['message' => $result['message'] ?? null, 'data' => $result['data'] ?? null],
            $result['success'] ? 201 : 422
        );
    }

    /**
     * GET /api/payroll/deduction-types
     */
    public function deductionTypes(): JsonResponse
    {
        return response()->json([
            'data' => PayrollDeductionType::where('is_active', true)->get(),
        ]);
    }

    /**
     * POST /api/payroll/deduction-types
     */
    public function storeDeductionType(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:payroll_deduction_types,name',
            'description' => 'nullable|string',
            'calculation_type' => 'required|in:fixed,percentage',
            'default_value' => 'required|numeric|min:0',
        ]);

        $type = PayrollDeductionType::create($request->only(['name', 'description', 'calculation_type', 'default_value']));

        return response()->json(['data' => $type], 201);
    }

    /**
     * DELETE /api/payroll/deduction-types/{id}
     * Deletes an unused type; archives (is_active = false) a type that has been used.
     */
    public function destroyDeductionType(int $id): JsonResponse
    {
        $type = PayrollDeductionType::find($id);

        if (!$type) {
            return response()->json(['message' => 'Deduction type not found'], 404);
        }

        $usedBy = $type->deductions()->count();

        if ($usedBy > 0) {
            $type->update(['is_active' => false]);

            return response()->json([
                'message' => "Deduction type archived (it was used by {$usedBy} deduction(s), so it was kept for history).",
            ]);
        }

        $type->delete();

        return response()->json(['message' => 'Deduction type deleted']);
    }

    /**
     * GET /api/payroll/settings
     */
    public function settings(): JsonResponse
    {
        return response()->json(['data' => PayrollSetting::all(['key', 'value', 'description'])]);
    }

    /**
     * PUT /api/payroll/settings/{key}
     */
    public function updateSetting(Request $request, string $key): JsonResponse
    {
        $request->validate(['value' => 'required|string']);

        $setting = PayrollSetting::where('key', $key)->first();

        if (!$setting) {
            return response()->json(['message' => 'Setting not found'], 404);
        }

        $setting->update(['value' => $request->value]);

        return response()->json(['data' => $setting]);
    }
}