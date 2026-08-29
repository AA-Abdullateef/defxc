<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'label',
        'icon',
        'active',
        'price_source',
        'price_source_id',
        'current_price',
        'price_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'current_price' => 'decimal:8',
            'price_updated_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}