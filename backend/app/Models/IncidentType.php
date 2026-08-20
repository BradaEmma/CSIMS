<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function incidents()
    {
        return $this->hasMany(Incident::class);
    }
}