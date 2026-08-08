<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'group_id',
        'product_key',
        'product_name',
        'latest_price',
        'latest_photo_url',
        'latest_message_id',
        'first_seen_at',
        'last_updated_at',
    ];

    protected $casts = [
        'latest_price'     => 'float',
        'latest_message_id'=> 'integer',
        'first_seen_at'    => 'datetime',
        'last_updated_at'  => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(TelegramGroup::class, 'group_id');
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }
}
