<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'type',
        'file_path',
        'original_filename',
        'uploaded_by',
    ];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}