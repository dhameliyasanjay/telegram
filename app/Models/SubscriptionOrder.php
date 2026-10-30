<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'notes',
        'activated_at',
        'paid_at',
    ];

    protected $casts = [
        'amount'             => 'float',
        'plan_duration_days' => 'integer',
        'activated_at'       => 'datetime',
        'paid_at'            => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class, 'subscription_order_id');
    }
}

