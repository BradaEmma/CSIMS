<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'approval_workflow_id',
        'current_level',
        'amount',
        'status',
        'submitted_by',
    ];

    protected $casts = [
        'current_level' => 'integer',
        'amount' => 'decimal:2',
    ];

    protected $appends = ['approvable_summary'];

    /**
     * Short human-readable summary of what this approval is actually
     * for, per approvable type. Generic by design — add a case here
     * whenever a new approvable type needs its own summary line.
     */
    public function getApprovableSummaryAttribute()
    {
        return match ($this->approvable_type) {
            'money_request' => optional(\App\Models\MoneyRequest::find($this->approvable_id))->reason,
            default => null,
        };
    }

    public function workflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function actions()
    {
        return $this->hasMany(ApprovalAction::class)->orderBy('created_at');
    }
}