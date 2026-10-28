<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanonicalProduct extends Model
{
    use HasFactory;

    protected $table = 'canonical_products';

    protected $fillable = [
        'canonical_key',
        'name',
        'brand',
        'model',
        'category',
        'primary_photo_url',
        'photo_hash',
        'cheapest_price',
        'cheapest_group_id',
        'highest_price',
        'offers_count',
        'last_observed_at',
    ];

    protected $casts = [
        'cheapest_price'   => 'float',
        'highest_price'    => 'float',
        'offers_count'     => 'integer',
        'last_observed_at' => 'datetime',
    ];

    /**
     * All current group offers for this canonical product.
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Product::class, 'canonical_product_id');
    }

    /**
     * Group offering the cheapest known price.
     */
    public function cheapestGroup(): BelongsTo
    {
        return $this->belongsTo(TelegramGroup::class, 'cheapest_group_id');
    }
}

