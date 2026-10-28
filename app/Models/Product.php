<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'canonical_product_id',
        'group_id',
        'product_key',
        'product_name',
        'latest_price',
        'latest_photo_url',
        'photo_hash',
        'latest_message_id',
        'first_seen_at',
        'last_updated_at',
    ];

    protected $casts = [
        'canonical_product_id' => 'integer',
        'latest_price'         => 'float',
        'latest_message_id'    => 'integer',
        'first_seen_at'        => 'datetime',
        'last_updated_at'      => 'datetime',
    ];

    public function canonicalProduct(): BelongsTo
    {
        return $this->belongsTo(CanonicalProduct::class, 'canonical_product_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TelegramGroup::class, 'group_id');
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }
}
