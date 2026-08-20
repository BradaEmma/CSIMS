<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'description',
        'required_guards',
        'zone',
        'morning_guards_required',
        'night_guards_required',
        'status',
        'contract_id',
    ];
    /*
    |---------------------------------------
    | Relationships
    |---------------------------------------
    */

    // A site has many guards (current)
    public function securityguards()
    {
        return $this->hasMany(Guard::class);
    }

    // A site has many assignments (history tracking)
    public function assignments()
    {
        return $this->hasMany(GuardAssignment::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }


}