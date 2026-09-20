<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoneyRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'amount',
        'reason',
        'site_id',
        'contract_id',
        'category',
        'attachment_path',
        'requested_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * The approval engine's tracking record for this request. Manual
     * relation (not morphOne) since ApprovalRequest.approvable_type
     * stores a short string key ('money_request'), matching the
     * existing $approvableMap convention — not a real class path.
     */
    public function approvalRequest()
    {
        return $this->hasOne(ApprovalRequest::class, 'approvable_id')
            ->where('approvable_type', 'money_request');
    }
}