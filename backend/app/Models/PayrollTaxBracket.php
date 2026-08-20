<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollTaxBracket extends Model
{
    protected $fillable = ['min_amount', 'max_amount', 'base_tax', 'rate_percentage'];
}