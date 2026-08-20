<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Guard;
use App\Models\PayrollRecord;
use App\Models\PayrollSetting;
use App\Models\PayrollTaxBracket;
use App\Models\PayrollDeduction;
use App\Models\PayrollDeductionType;
use App\Models\RosterAssignment;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function generatePayrollForGuard(int $guardId, string $period): array
    {
        return DB::transaction(function () use ($guardId, $period) {

            $guard = Guard::find($guardId);

            if (!$guard) {
                return ['success' => false, 'message' => 'Guard not found'];
            }

            if (!$guard->daily_rate || $guard->daily_rate <= 0) {
                return ['success' => false, 'message' => 'Guard has no daily rate set — cannot calculate payroll'];
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

            if ($daysWorked === 0) {
                return ['success' => false, 'message' => 'No roster assignments found for this guard in this period'];
            }

            $grossPay = $daysWorked * $guard->daily_rate;

            // Rest-day premium: is_overtime days are already paid at the normal
            // 1.0x rate within $grossPay above; this adds the extra 0.5x (or
            // whatever restday_overtime_multiplier - 1 currently is) on top.
            $restDayMultiplier = (float) (PayrollSetting::where('key', 'restday_overtime_multiplier')->value('value') ?? 1.5);
            $restDayPremium = $overtimeDays * $guard->daily_rate * ($restDayMultiplier - 1);

            // Extra-hour overtime: hours actually worked beyond the standard
            // shift length on any day, paid at the extra-hours multiplier.
            $standardShiftHours = (float) (PayrollSetting::where('key', 'standard_shift_hours')->value('value') ?? 12);
            $extraHoursMultiplier = (float) (PayrollSetting::where('key', 'extra_hours_overtime_multiplier')->value('value') ?? 1.0);
            $hourlyRate = $guard->daily_rate / $standardShiftHours;

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

            $nssfDeduction = 0;
            if ($guard->nssf_applicable) {
                $nssfRate = (float) (PayrollSetting::where('key', 'nssf_employee_rate')->value('value') ?? 0);
                $nssfDeduction = round($totalGross * ($nssfRate / 100), 2);
            }

            $payeDeduction = 0;
            if ($guard->paye_applicable) {
                $payeIncome = $totalGross - $nssfDeduction;
                $payeDeduction = $this->calculatePaye($payeIncome);
            }

            $otherDeductions = PayrollDeduction::where('guard_id', $guardId)
                ->where('period', $period)
                ->sum('amount');

            $netPay = $totalGross - $nssfDeduction - $payeDeduction - $otherDeductions;

            $data = [
                'days_worked'             => $daysWorked,
                'overtime_days'           => $overtimeDays,
                'gross_pay'               => $grossPay,
                'overtime_pay'            => $overtimePay,
                'nssf_deduction'          => $nssfDeduction,
                'paye_deduction'          => $payeDeduction,
                'other_deductions_total'  => $otherDeductions,
                'net_pay'                 => $netPay,
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

    public function generateBulkForPeriod(string $period): array
    {
        $guardIds = RosterAssignment::whereYear('date', explode('-', $period)[0])
            ->whereMonth('date', explode('-', $period)[1])
            ->pluck('guard_id')
            ->unique();

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

        $amount = $amountOverride ?? $type->default_value;

        if ($type->calculation_type === 'percentage') {
            $guard = Guard::find($guardId);
            $amount = ($guard->daily_rate ?? 0) * ($amount / 100);
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

    public function updateStatus(int $recordId, string $status): array
    {
        if (!in_array($status, ['draft', 'finalized', 'paid'], true)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        $record = PayrollRecord::find($recordId);

        if (!$record) {
            return ['success' => false, 'message' => 'Payroll record not found'];
        }

        $record->update(['status' => $status]);

        return ['success' => true, 'data' => $record];
    }
}