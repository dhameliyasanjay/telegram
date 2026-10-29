<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionOrder extends Model
{
    use HasFactory;

    protected $table = 'subscription_orders';

    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_city',
        'amount',
        'plan_duration_days',
        'payment_method',
        'status',
        'notes',
        'activated_at',
    ];

    protected $casts = [
        'amount'             => 'float',
        'plan_duration_days' => 'integer',
        'activated_at'       => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
