<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramGroup extends Model
{
    protected $table = 'telegram_groups';

    protected $fillable = [
        'telegram_id',
        'title',
        'invite_link',
        'phone',
        'location',
        'address',
        'member_count',
        'photo_url',
        'is_active',
        'sync_status',
        'last_error',
        'added_at',
        'last_synced_at',
    ];

    protected $casts = [
        'telegram_id'    => 'integer',
        'member_count'   => 'integer',
        'is_active'      => 'boolean',
        'added_at'       => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Scope query to only monitored/active groups.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'group_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(GroupSubmission::class, 'telegram_group_id');
    }
}
