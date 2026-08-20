<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RosterGenerationLog extends Model
{
    protected $fillable = [
        'start_date',
        'end_date',
        'triggered_by',
        'assignments_created',
        'shortages_count',
        'shortages_detail',
        'status',
        'error_message',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'shortages_detail' => 'array',
    ];

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}