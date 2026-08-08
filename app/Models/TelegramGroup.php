<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramGroup extends Model
{
    protected $table = 'telegram_groups';

    protected $fillable = [
        'telegram_id',
        'title',
        'invite_link',
        'member_count',
        'photo_url',
        'added_at',
        'last_synced_at',
    ];

    protected $casts = [
        'telegram_id'    => 'integer',
        'member_count'   => 'integer',
        'added_at'       => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'group_id');
    }
}
