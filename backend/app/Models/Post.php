<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'name',
        'morning_guards_required',
        'night_guards_required',
        'status',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function rosterAssignments()
    {
        return $this->hasMany(RosterAssignment::class);
    }
}