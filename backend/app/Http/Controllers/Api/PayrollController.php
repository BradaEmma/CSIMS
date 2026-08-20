<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use App\Models\PayrollDeductionType;
use App\Models\PayrollSetting;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
        ]);

        $result = $this->payrollService->generatePayrollForGuard($request->guard_id, $request->period);

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

        $query = PayrollRecord::with('securityGuard')->orderByDesc('period');

        if ($request->guard_id) {
            $query->where('guard_id', $request->guard_id);
        }

        if ($request->period) {
            $query->where('period', $request->period);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return response()->json(['data' => $query->paginate(30)]);
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
     * PATCH /api/payroll/{id}/status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:draft,finalized,paid',
        ]);

        $result = $this->payrollService->updateStatus($id, $request->status);

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
            'guard_id' => 'required|integer|exists:guards,id',
            'payroll_deduction_type_id' => 'required|integer|exists:payroll_deduction_types,id',
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'required|string',
            'period' => 'required|date_format:Y-m',
        ]);

        $result = $this->payrollService->addDeduction(
            $request->guard_id,
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