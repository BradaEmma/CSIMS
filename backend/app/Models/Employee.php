<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'national_id',
        'department_id',
        'position',
        'status',
        'hire_date',
        'created_by',
    ];

    protected $casts = [
        'hire_date' => 'date',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

        public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function guardProfile()
    {
    return $this->hasOne(Guard::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}