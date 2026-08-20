<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Models\IncidentType;
use App\Models\Site;
use Carbon\Carbon;

class IncidentService
{
    public function report(array $data, int $reportedBy): array
    {
        $site = Site::find($data['site_id'] ?? null);

        if (!$site) {
            return [
                'success' => false,
                'message' => 'Site not found',
            ];
        }

        $incidentType = IncidentType::where('id', $data['incident_type_id'] ?? null)
            ->where('is_active', true)
            ->first();

        if (!$incidentType) {
            return [
                'success' => false,
                'message' => 'Invalid or inactive incident type',
            ];
        }

        if (!in_array($data['severity'] ?? null, ['low', 'medium', 'high', 'critical'], true)) {
            return [
                'success' => false,
                'message' => 'Invalid severity level',
            ];
        }

        $incident = Incident::create([
            'site_id'               => $site->id,
            'roster_assignment_id'  => $data['roster_assignment_id'] ?? null,
            'reported_by'           => $reportedBy,
            'incident_type_id'      => $incidentType->id,
            'severity'              => $data['severity'],
            'description'           => $data['description'] ?? '',
            'occurred_at'           => $data['occurred_at'] ?? Carbon::now(),
            'status'                => 'open',
        ]);

        return [
            'success' => true,
            'data' => $incident->load(['site', 'incidentType', 'reportedBy']),
        ];
    }

    public function addAttachment(int $incidentId, string $filePath, int $uploadedBy): array
    {
        $incident = Incident::find($incidentId);

        if (!$incident) {
            return [
                'success' => false,
                'message' => 'Incident not found',
            ];
        }

        $attachment = IncidentAttachment::create([
            'incident_id' => $incident->id,
            'file_path'   => $filePath,
            'uploaded_by' => $uploadedBy,
        ]);

        return [
            'success' => true,
            'data' => $attachment,
        ];
    }

    public function resolve(int $incidentId, string $resolutionNotes): array
    {
        $incident = Incident::find($incidentId);

        if (!$incident) {
            return [
                'success' => false,
                'message' => 'Incident not found',
            ];
        }

        if ($incident->status === 'closed') {
            return [
                'success' => false,
                'message' => 'Incident is already closed',
            ];
        }

        $incident->update([
            'status'           => 'resolved',
            'resolution_notes' => $resolutionNotes,
            'resolved_at'      => Carbon::now(),
        ]);

        return [
            'success' => true,
            'data' => $incident,
        ];
    }

    public function updateStatus(int $incidentId, string $status): array
    {
        if (!in_array($status, ['open', 'under_review', 'resolved', 'closed'], true)) {
            return [
                'success' => false,
                'message' => 'Invalid status',
            ];
        }

        $incident = Incident::find($incidentId);

        if (!$incident) {
            return [
                'success' => false,
                'message' => 'Incident not found',
            ];
        }

        $incident->update(['status' => $status]);

        return [
            'success' => true,
            'data' => $incident,
        ];
    }
}