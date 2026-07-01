<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositPhoto extends Model
{
    use HasUuid;

    protected $fillable = ['transaction_id', 'img'];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function url(): string
    {
        return asset('storage/' . $this->img);
    }
}