<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceHistory extends Model
{
    protected $table = 'price_history';

    protected $fillable = [
        'product_id',
        'price',
        'photo_url',
        'message_id',
        'message_text',
        'posted_at',
        'recorded_at',
    ];

    protected $casts = [
        'price'       => 'float',
        'message_id'  => 'integer',
        'posted_at'   => 'datetime',
        'recorded_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
