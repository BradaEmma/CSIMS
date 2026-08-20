<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'status',
        'created_by',
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}