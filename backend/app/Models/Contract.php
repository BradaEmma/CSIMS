<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'client_id',
        'reference_number',
        'start_date',
        'end_date',
        'monthly_fee',
        'terms',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}