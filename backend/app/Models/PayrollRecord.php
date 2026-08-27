<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollRecord extends Model
{
    protected $fillable = [
        'guard_id',
        'period',
        'days_worked',
        'overtime_days',
        'gross_pay',
        'overtime_pay',
        'nssf_deduction',
        'paye_deduction',
        'other_deductions_total',
        'net_pay',
        'status',
    ];

    public function securityGuard()
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}