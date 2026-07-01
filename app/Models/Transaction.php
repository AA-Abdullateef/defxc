<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasUuid;

    protected $fillable = [
        'wallet_id',
        'amount',
        'type',
        'asset_id',
        'sub_method_id',
        'meta',
        'status',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:5',
            'meta'   => 'array',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function subMethod(): BelongsTo
    {
        return $this->belongsTo(SubMethod::class);
    }

    public function depositPhoto(): HasOne
    {
        return $this->hasOne(DepositPhoto::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForUser($query, string $userId)
    {
        return $query->whereHas('wallet', fn ($walletQuery) =>
            $walletQuery->where('user_id', $userId)
        );
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeWithStatus($query, string|array $status)
    {
        return is_array($status)
            ? $query->whereIn('status', $status)
            : $query->where('status', $status);
    }

    public function scopeForAsset($query, string $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    // ─── Status helpers ───────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::Pending->value;
    }

    public function isCompleted(): bool
    {
        return $this->status === TransactionStatus::Completed->value;
    }

    public function isCancelled(): bool
    {
        return $this->status === TransactionStatus::Cancelled->value;
    }

    public function statusLabel(): string
    {
        return TransactionStatus::from($this->status)->label();
    }

    public function typeLabel(): string
    {
        return TransactionType::from($this->type)->label();
    }
}
