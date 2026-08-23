<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_request_id',
        'approval_workflow_level_id',
        'user_id',
        'action',
        'comment',
    ];

    public function request()
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function level()
    {
        return $this->belongsTo(ApprovalWorkflowLevel::class, 'approval_workflow_level_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}