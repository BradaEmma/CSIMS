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