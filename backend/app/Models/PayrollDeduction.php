<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollDeduction extends Model
{
    protected $fillable = [
        'guard_id',
        'payroll_deduction_type_id',
        'amount',
        'reason',
        'applied_by',
        'period',
    ];

    public function securityGuard()
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function type()
    {
        return $this->belongsTo(PayrollDeductionType::class, 'payroll_deduction_type_id');
    }

    public function appliedBy()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}