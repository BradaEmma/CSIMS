<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Guard;
use App\Models\Site;
use App\Models\User;

class GuardAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'guard_id',
        'site_id',
        'assigned_by',
        'start_date',
        'end_date',
        'status',
    ];

    /*
    |---------------------------------------
    | Relationships
    |---------------------------------------
    */

    public function assignedGuard()
    {
        return $this->belongsTo(\App\Models\Guard::class, 'guard_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}