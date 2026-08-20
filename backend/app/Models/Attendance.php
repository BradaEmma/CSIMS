<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'guard_id',
        'site_id',
        'shift_instance_id',
        'roster_assignment_id',
        'status',
        'notes',
        'recorded_by',
        'check_in_at',
        'check_out_at',
        'is_late',
        'minutes_late',
        'hours_worked',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'is_late' => 'boolean',
        'hours_worked' => 'decimal:2',
    ];

    public function securityGuard()
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function shiftInstance()
    {
        return $this->belongsTo(ShiftInstance::class);
    }

    public function rosterAssignment()
    {
        return $this->belongsTo(RosterAssignment::class);
    }
}