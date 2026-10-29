<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    /**
     * Get typed value.
     */
    public function getCastValueAttribute()
    {
        return match ($this->type) {
            'integer', 'int'   => (int) $this->value,
            'float', 'double'  => (float) $this->value,
            'boolean', 'bool'  => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array'    => json_decode($this->value, true) ?? [],
            default            => $this->value,
        };
    }
}
