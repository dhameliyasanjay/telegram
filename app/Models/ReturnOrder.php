<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnOrder extends Model
{
    protected $fillable = [
        'return_order_number',
        'date',
    ];
}
