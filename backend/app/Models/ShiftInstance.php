<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftInstance extends Model
{
    protected $fillable = [
        'schedule_id',
        'shift_date',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function assignments()
    {
        return $this->hasMany(ScheduleAssignment::class);
    }
}
