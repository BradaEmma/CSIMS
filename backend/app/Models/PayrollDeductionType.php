<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollDeductionType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'calculation_type',
        'default_value',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function deductions()
    {
        return $this->hasMany(PayrollDeduction::class);
    }
}