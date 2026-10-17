<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Guard;
use App\Models\PayrollRecord;
use App\Models\PayrollSetting;
use App\Models\PayrollTaxBracket;
use App\Models\PayrollDeduction;
use App\Models\PayrollDeductionType;
use App\Models\RosterAssignment;
use App\Models\ApprovalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollService
{
    public function __construct(private ApprovalWorkflowService $approvalService) {}

    public function generatePayrollForGuard(int $guardId, string $period, ?int $unpaidDays = null): array
    {
        return DB::transaction(function () use ($guardId, $period, $unpaidDays) {

            $guard = Guard::find($guardId);

            if (!$guard) {
                return ['success' => false, 'message' => 'Guard not found'];
            }

            if (!$guard->monthly_basic || $guard->monthly_basic <= 0) {
                return ['success' => false, 'message' => 'Guard has no monthly basic set — cannot calculate payroll'];
            }

            $existing = PayrollRecord::where('guard_id', $guardId)->where('period', $period)->first();

            if ($existing && $existing->status !== 'draft') {
                return ['success' => false, 'message' => "Payroll is already {$existing->status} for this period — cannot regenerate"];
            }

            [$year, $month] = explode('-', $period);
            $assignments = RosterAssignment::where('guard_id', $guardId)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get();

            $daysWorked = $assignments->pluck('date')->unique()->count();
            $overtimeDays = $assignments->where('is_overtime', true)->pluck('date')->unique()->count();

            $daysInMonth = \Carbon\Carbon::createFromDate((int) $year, (int) $month, 1)->daysInMonth;

            // Unpaid days: use the value passed in; when regenerating a draft
            // without a new value, keep what was entered last time.
            $unpaidDays = $unpaidDays ?? (int) ($existing->unpaid_days ?? 0);

            if ($unpaidDays < 0 || $unpaidDays > $daysInMonth) {
                return ['success' => false, 'message' => "Unpaid days must be between 0 and {$daysInMonth} for this period"];
            }

            // Model B: the daily rate is derived each month from the basic.
            $dailyRate = $guard->monthly_basic / $daysInMonth;
            $grossPay = round($guard->monthly_basic - ($unpaidDays * $dailyRate), 2);

            $restDayMultiplier = (float) (PayrollSetting::where('key', 'restday_overtime_multiplier')->value('value') ?? 1.5);
            $restDayPremium = $overtimeDays * $dailyRate * ($restDayMultiplier - 1);

            $standardShiftHours = (float) (PayrollSetting::where('key', 'standard_shift_hours')->value('value') ?? 12);
            $extraHoursMultiplier = (float) (PayrollSetting::where('key', 'extra_hours_overtime_multiplier')->value('value') ?? 1.0);
            $hourlyRate = $dailyRate / $standardShiftHours;

            $extraHoursPay = Attendance::where('guard_id', $guardId)
                ->whereIn('roster_assignment_id', $assignments->pluck('id'))
                ->whereNotNull('hours_worked')
                ->where('hours_worked', '>', $standardShiftHours)
                ->get()
                ->sum(function ($record) use ($standardShiftHours, $hourlyRate, $extraHoursMultiplier) {
                    return ($record->hours_worked - $standardShiftHours) * $hourlyRate * $extraHoursMultiplier;
                });

            $overtimePay = round($restDayPremium + $extraHoursPay, 2);
            $totalGross = $grossPay + $overtimePay;

            $otherDeductions = (float) PayrollDeduction::where('guard_id', $guardId)
                ->where('period', $period)
                ->sum('amount');

            $statutory = $this->calculateStatutory(
                (float) $totalGross,
                (bool) $guard->nssf_applicable,
                (bool) $guard->paye_applicable,
                $otherDeductions
            );

            $nssfDeduction = $statutory['nssf_deduction'];
            $payeDeduction = $statutory['paye_deduction'];
            $netPay = $statutory['net_pay'];

            $data = [
                'days_worked'             => $daysWorked,
                'unpaid_days'             => $unpaidDays,
                'overtime_days'           => $overtimeDays,
                'gross_pay'               => $grossPay,
                'overtime_pay'            => $overtimePay,
                'nssf_deduction'          => $nssfDeduction,
                'paye_deduction'          => $payeDeduction,
                'other_deductions_total'  => $otherDeductions,
                'net_pay'                 => $netPay,
                'employee_id'             => $guard->employee_id,
            ];

            if ($existing) {
                $existing->update($data);
                $record = $existing->fresh();
            } else {
                $record = PayrollRecord::create(array_merge($data, [
                    'guard_id' => $guardId,
                    'period'   => $period,
                    'status'   => 'draft',
                ]));
            }

            return ['success' => true, 'data' => $record];
        });
    }

    /**
     * Salaried staff: fixed monthly salary. No roster / days_worked.
     * Same NSSF + PAYE rules as guards (Tanzania Mainland private sector).
     */
    public function generatePayrollForSalariedEmployee(int $employeeId, string $period, ?int $unpaidDays = null): array
    {
        return DB::transaction(function () use ($employeeId, $period, $unpaidDays) {
            $employee = Employee::find($employeeId);

            if (!$employee) {
                return ['success' => false, 'message' => 'Employee not found'];
            }

            if (($employee->pay_type ?? null) !== 'monthly') {
                return ['success' => false, 'message' => 'Employee is not set to monthly pay_type'];
            }

            if (!$employee->monthly_salary || $employee->monthly_salary <= 0) {
                return ['success' => false, 'message' => 'Employee has no monthly_salary set — cannot calculate payroll'];
            }

            $existing = PayrollRecord::where('employee_id', $employeeId)
                ->where('period', $period)
                ->first();

            if ($existing && $existing->status !== 'draft') {
                return ['success' => false, 'message' => "Payroll is already {$existing->status} for this period — cannot regenerate"];
            }

            [$year, $month] = explode('-', $period);
            $daysInMonth = \Carbon\Carbon::createFromDate((int) $year, (int) $month, 1)->daysInMonth;

            // Keep the stored value when regenerating a draft without a new one.
            $unpaidDays = $unpaidDays ?? (int) ($existing->unpaid_days ?? 0);

            if ($unpaidDays < 0 || $unpaidDays > $daysInMonth) {
                return ['success' => false, 'message' => "Unpaid days must be between 0 and {$daysInMonth} for this period"];
            }

            $dailyValue = (float) $employee->monthly_salary / $daysInMonth;
            $grossPay = round((float) $employee->monthly_salary - ($unpaidDays * $dailyValue), 2);
            $overtimePay = 0.0;
            $totalGross = $grossPay + $overtimePay;

            $otherDeductions = 0.0;
            if (Schema::hasColumn('payroll_deductions', 'employee_id')) {
                $otherDeductions = (float) PayrollDeduction::where('employee_id', $employeeId)
                    ->where('period', $period)
                    ->sum('amount');
            }

            $statutory = $this->calculateStatutory(
                $totalGross,
                (bool) ($employee->nssf_applicable ?? true),
                (bool) ($employee->paye_applicable ?? true),
                $otherDeductions
            );

            $nssfDeduction = $statutory['nssf_deduction'];
            $payeDeduction = $statutory['paye_deduction'];
            $netPay = $statutory['net_pay'];

            $data = [
                'days_worked'            => 0,
                'unpaid_days'            => $unpaidDays,
                'overtime_days'          => 0,
                'gross_pay'              => $grossPay,
                'overtime_pay'           => $overtimePay,
                'nssf_deduction'         => $nssfDeduction,
                'paye_deduction'         => $payeDeduction,
                'other_deductions_total' => $otherDeductions,
                'net_pay'                => $netPay,
            ];

            if ($existing) {
                $existing->update($data);
                $record = $existing->fresh();
            } else {
                $record = PayrollRecord::create(array_merge($data, [
                    'employee_id' => $employeeId,
                    'guard_id'    => null,
                    'period'      => $period,
                    'status'      => 'draft',
                ]));
            }

            return ['success' => true, 'data' => $record];
        });
    }

    public function generateBulkForPeriod(string $period): array
    {
        [$year, $month] = explode('-', $period);

        // Every active guard (rostered or not), plus anyone with roster rows
        // in this month even if now inactive (e.g. left mid-month). Deduplicated.
        $rosteredGuardIds = RosterAssignment::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->pluck('guard_id');

        $guardIds = Guard::where('status', 'active')
            ->pluck('id')
            ->merge($rosteredGuardIds)
            ->unique()
            ->values();

        // Salaried (monthly) staff who are not guards, so nobody is paid twice.
        $employeeIds = Employee::where('status', 'active')
            ->where('pay_type', 'monthly')
            ->whereNotIn('id', Guard::whereNotNull('employee_id')->pluck('employee_id'))
            ->pluck('id');

        $employeeResults = [];
        foreach ($employeeIds as $employeeId) {
            $employeeResults[$employeeId] = $this->generatePayrollForSalariedEmployee($employeeId, $period);
        }

        $results = [];

        foreach ($guardIds as $guardId) {
            $results[$guardId] = $this->generatePayrollForGuard($guardId, $period);
        }

        return [
            'success' => true,
            'total_guards' => $guardIds->count(),
            'succeeded' => collect($results)->where('success', true)->count(),
            'failed' => collect($results)->where('success', false)->count(),
            'details' => $results,
            'total_employees' => $employeeIds->count(),
            'employees_succeeded' => collect($employeeResults)->where('success', true)->count(),
            'employees_failed' => collect($employeeResults)->where('success', false)->count(),
            'employee_details' => $employeeResults,
        ];
    }

    /**
     * Shared Tanzania statutory calculation for one payroll line
     * (guard or salaried). NSSF employee share on total gross, PAYE on
     * (total gross - employee NSSF), net = total gross - NSSF - PAYE - other
     * deductions. The NSSF rate comes from payroll_settings as a whole
     * percentage; if the setting is missing it falls back to the statutory 10.
     */
    private function calculateStatutory(float $totalGross, bool $nssfApplicable, bool $payeApplicable, float $otherDeductions): array
    {
        $nssfDeduction = 0.0;
        if ($nssfApplicable) {
            $nssfRate = (float) (PayrollSetting::where('key', 'nssf_employee_rate')->value('value') ?? 10);
            $nssfDeduction = round($totalGross * ($nssfRate / 100), 2);
        }

        $payeDeduction = 0.0;
        if ($payeApplicable) {
            $payeDeduction = $this->calculatePaye($totalGross - $nssfDeduction);
        }

        return [
            'nssf_deduction' => $nssfDeduction,
            'paye_deduction' => $payeDeduction,
            'net_pay'        => $totalGross - $nssfDeduction - $payeDeduction - $otherDeductions,
        ];
    }

    private function calculatePaye(float $income): float
    {
        $bracket = PayrollTaxBracket::where('min_amount', '<=', $income)
            ->where(function ($q) use ($income) {
                $q->whereNull('max_amount')->orWhere('max_amount', '>=', $income);
            })
            ->first();

        if (!$bracket) {
            return 0;
        }

        $taxableAboveMin = max($income - $bracket->min_amount, 0);
        $tax = $bracket->base_tax + ($taxableAboveMin * ($bracket->rate_percentage / 100));

        return round($tax, 2);
    }

    public function addDeduction(int $guardId, int $deductionTypeId, ?float $amountOverride, string $reason, int $appliedBy, string $period): array
    {
        $type = PayrollDeductionType::where('id', $deductionTypeId)->where('is_active', true)->first();

        if (!$type) {
            return ['success' => false, 'message' => 'Invalid or inactive deduction type'];
        }

        $guard = Guard::find($guardId);

        if (!$guard) {
            return ['success' => false, 'message' => 'Guard not found'];
        }

        $amount = $amountOverride ?? $type->default_value;

        // Model B: derive the daily rate for this period from the monthly basic.
        [$periodYear, $periodMonth] = explode('-', $period);
        $daysInMonth = \Carbon\Carbon::createFromDate((int) $periodYear, (int) $periodMonth, 1)->daysInMonth;
        $dailyRate = $guard->monthly_basic ? $guard->monthly_basic / $daysInMonth : 0;

        if ($type->calculation_type === 'percentage') {
            $amount = $dailyRate * ($amount / 100);
        }

        if ($dailyRate > 0) {
            $maxMultiplier = (float) (PayrollSetting::where('key', 'max_deduction_multiplier')->value('value') ?? 31);
            $ceiling = $dailyRate * $maxMultiplier;

            if ($amount > $ceiling) {
                return [
                    'success' => false,
                    'message' => "Deduction amount ({$amount}) exceeds the safety ceiling ({$ceiling}) for this guard's daily rate — please double check the figure.",
                ];
            }
        }

        $deduction = PayrollDeduction::create([
            'guard_id' => $guardId,
            'payroll_deduction_type_id' => $deductionTypeId,
            'amount' => $amount,
            'reason' => $reason,
            'applied_by' => $appliedBy,
            'period' => $period,
        ]);

        return ['success' => true, 'data' => $deduction];
    }

    /**
     * Salaried-employee equivalent of addDeduction(). Percentage-type
     * deductions are calculated off monthly_salary, and the safety
     * ceiling caps at one month's salary rather than the guard path's
     * daily_rate x max_deduction_multiplier setting, since there is no
     * daily concept for a salaried employee.
     */
    public function addDeductionForEmployee(int $employeeId, int $deductionTypeId, ?float $amountOverride, string $reason, int $appliedBy, string $period): array
    {
        $type = PayrollDeductionType::where('id', $deductionTypeId)->where('is_active', true)->first();

        if (!$type) {
            return ['success' => false, 'message' => 'Invalid or inactive deduction type'];
        }

        $employee = Employee::find($employeeId);

        if (!$employee) {
            return ['success' => false, 'message' => 'Employee not found'];
        }

        $amount = $amountOverride ?? $type->default_value;

        if ($type->calculation_type === 'percentage') {
            $amount = ($employee->monthly_salary ?? 0) * ($amount / 100);
        }

        if ($employee->monthly_salary) {
            $ceiling = $employee->monthly_salary;

            if ($amount > $ceiling) {
                return [
                    'success' => false,
                    'message' => "Deduction amount ({$amount}) exceeds the safety ceiling ({$ceiling}) for this employee's monthly salary — please double check the figure.",
                ];
            }
        }

        $deduction = PayrollDeduction::create([
            'employee_id' => $employeeId,
            'payroll_deduction_type_id' => $deductionTypeId,
            'amount' => $amount,
            'reason' => $reason,
            'applied_by' => $appliedBy,
            'period' => $period,
        ]);

        return ['success' => true, 'data' => $deduction];
    }

    public function updateStatus(int $recordId, string $status, ?int $actingUserId = null): array
    {
        if (!in_array($status, ['draft', 'finalized', 'paid'], true)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        $record = PayrollRecord::find($recordId);

        if (!$record) {
            return ['success' => false, 'message' => 'Payroll record not found'];
        }

        if ($status === 'paid' && $record->status === 'finalized') {
            return $this->requestPayrollApproval($record, $actingUserId);
        }

        $record->update(['status' => $status]);

        return ['success' => true, 'data' => $record];
    }

    protected function requestPayrollApproval(PayrollRecord $record, ?int $actingUserId): array
    {
        $latest = ApprovalRequest::where('approvable_type', 'payroll_record')
            ->where('approvable_id', $record->id)
            ->orderByDesc('created_at')
            ->first();

        if ($latest && $latest->status === 'pending') {
            return [
                'success' => false,
                'message' => 'This payroll record is awaiting approval. It will be marked paid once approved.',
                'data' => $record,
            ];
        }

        if ($latest && $latest->status === 'approved') {
            $record->update(['status' => 'paid']);
            return [
                'success' => true,
                'message' => 'Payroll record marked as paid.',
                'data' => $record->fresh(),
            ];
        }

        if (!$actingUserId) {
            return [
                'success' => false,
                'message' => 'Unable to submit for approval: no acting user provided.',
            ];
        }

        $result = $this->approvalService->submit(
            'payroll_record',
            $record->id,
            'payroll',
            $actingUserId,
            (float) $record->net_pay
        );

        if (!$result['success']) {
            return $result;
        }

        return [
            'success' => true,
            'message' => 'Submitted for approval. Payroll record will be marked paid once approved.',
            'data' => $record,
        ];
    }
}