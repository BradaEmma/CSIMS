<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflowLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_workflow_id',
        'sequence',
        'name',
        'approver_role',
        'min_amount',
        'max_amount',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
    ];

    public function workflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function actions()
    {
        return $this->hasMany(ApprovalAction::class);
    }
}