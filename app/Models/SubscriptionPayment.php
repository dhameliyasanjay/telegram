<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $table = 'subscription_payments';

    protected $fillable = [
        'user_id',
        'subscription_order_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'amount',
        'currency',
        'status',
        'payment_method',
        'error_code',
        'error_description',
        'gateway_response',
        'paid_at',
    ];

    protected $casts = [
        'amount'           => 'float',
        'gateway_response' => 'array',
        'paid_at'          => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SubscriptionOrder::class, 'subscription_order_id');
    }
}

