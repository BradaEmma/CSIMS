<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $fillable = [
        'site_id',
        'roster_assignment_id',
        'reported_by',
        'incident_type_id',
        'severity',
        'description',
        'occurred_at',
        'status',
        'resolution_notes',
        'resolved_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function rosterAssignment()
    {
        return $this->belongsTo(RosterAssignment::class);
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function incidentType()
    {
        return $this->belongsTo(IncidentType::class);
    }

    public function attachments()
    {
        return $this->hasMany(IncidentAttachment::class);
    }
}