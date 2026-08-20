<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guard extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'national_id',
        'status',
        'shift_type',
        'site_id',
        'created_by',
        'daily_rate',
        'nssf_applicable',
        'paye_applicable',
    ];

    protected $casts = [
        'nssf_applicable' => 'boolean',
        'paye_applicable' => 'boolean',
    ];

    /*
    |---------------------------------------
    | Relationships
    |---------------------------------------
    */

    // A guard belongs to a site (current assignment)
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    // A guard can have many assignments (history tracking)
    public function assignments()
    {
        return $this->hasMany(GuardAssignment::class);
    }

    // Who created the guard (admin/supervisor)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Payroll history for this guard
    public function payrollRecords()
    {
        return $this->hasMany(PayrollRecord::class);
    }

    // Ad-hoc deductions applied to this guard
    public function payrollDeductions()
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}