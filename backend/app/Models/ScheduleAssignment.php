<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Guard;
use App\Models\Schedule;

class ScheduleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'shift_instance_id',
        'guard_id',
        'status',
    ];

    /*
    |---------------------------------------
    | Relationships
    |---------------------------------------
    */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function shiftInstance()
    {
        return $this->belongsTo(ShiftInstance::class);
    }

    // ✅ FIX: DO NOT use "guard()"
    public function securityGuard()
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }
}