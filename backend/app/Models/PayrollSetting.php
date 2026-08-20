<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    protected $fillable = ['key', 'value', 'description'];
}