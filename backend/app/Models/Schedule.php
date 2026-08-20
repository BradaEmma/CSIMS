<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'schedule_date',
        'site_id',
        'zone',
        'shift',
        'required_guards',
        'status',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function assignments()
    {
        return $this->hasMany(ScheduleAssignment::class);
    }
}