<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentAttachment extends Model
{
    protected $fillable = [
        'incident_id',
        'file_path',
        'uploaded_by',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}