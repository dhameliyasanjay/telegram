<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogueProduct extends Model
{
    protected $fillable = [
        'photo_path',
        'product_name',
        'sku_id',
        'bank_settlement',
        'market_price',
        'qty',
        'group_name',
        'status',
    ];

    protected $appends = ['photo_url', 'group_name_array'];

    /**
     * Always return group_name as an array (handles both plain strings and JSON).
     */
    public function getGroupNameArrayAttribute(): array
    {
        $raw = $this->attributes['group_name'] ?? null;
        if (!$raw) return [];
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return $decoded;
        return [$raw]; // plain string fallback
    }

    /**
     * Store group_name always as JSON.
     */
    public function setGroupNameAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['group_name'] = json_encode(array_values(array_filter($value)));
        } elseif (is_string($value) && str_starts_with(ltrim($value), '[')) {
            $this->attributes['group_name'] = $value; // already JSON
        } elseif ($value) {
            $this->attributes['group_name'] = json_encode([$value]);
        } else {
            $this->attributes['group_name'] = null;
        }
    }

    /**
     * When reading group_name directly, return decoded array or null.
     */
    public function getGroupNameAttribute(): ?array
    {
        $raw = $this->attributes['group_name'] ?? null;
        if (!$raw) return null;
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return $decoded;
        return [$raw];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }
        return asset('storage/' . $this->photo_path);
    }
}
