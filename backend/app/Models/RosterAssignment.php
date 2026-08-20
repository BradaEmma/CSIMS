<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Guard;
use App\Models\Site;

class RosterAssignment extends Model
{
    protected $fillable = [
        'guard_id',
        'site_id',
        'date',
        'shift',
        'is_overtime',
        'is_double_shift',
        'consent_confirmed_by',
        'generated_by_system',
    ];

    protected $casts = [
        'is_overtime' => 'boolean',
        'is_double_shift' => 'boolean',
    ];

    // FIXED RELATIONSHIP NAME (IMPORTANT)
    public function assignedGuard()
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function consentConfirmedBy()
    {
        return $this->belongsTo(User::class, 'consent_confirmed_by');
    }
}