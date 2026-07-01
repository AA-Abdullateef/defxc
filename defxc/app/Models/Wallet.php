<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Wallet extends Authenticatable
{
    use HasApiTokens, HasUuid;

    protected $casts = [
    // 💡 This ensures the data is AES-256 encrypted in the DB automatically
        'mnemonic' => 'encrypted', 
    ];

    protected $fillable = ['id', 'user_id', 'mnemonic', 'mnemonic_hash', 'fingerprint', 'public_key'];

    protected $hidden = ['mnemonic_hash'];

    protected static function booted(): void
    {
        static::creating(function (Wallet $wallet): void {
            if (! $wallet->getKey()) {
                $wallet->{$wallet->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // A wallet owns transactions, card requests, connections
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function cardRequests(): HasMany
    {
        return $this->hasMany(CardRequest::class);
    }

    public function walletConnections(): HasMany
    {
        return $this->hasMany(WalletConnection::class);
    }

    /**
     * Resolve the notification email for this wallet.
     * Returns the linked user's email, or null if no user or no email set.
     * Used before sending any transaction notification.
     */
    public function notificationEmail(): ?string
    {
        return $this->user?->email ?? null;
    }

    /**
     * Generate a consistent fingerprint for a mnemonic phrase.
     * Used to find a mnemonic without decrypting every row.
     * HMAC with APP_KEY ensures it can't be reversed externally.
     */
    public static function fingerprint(string $phrase): string
    {
        return hash_hmac('sha256', self::normalizeMnemonic($phrase), config('app.key'));
    }

    public static function mnemonicHash(string $phrase): string
    {
        return hash('sha256', self::normalizeMnemonic($phrase));
    }

    public static function publicKey(string $phrase): string
    {
        return hash_hmac('sha256', 'public:' . self::normalizeMnemonic($phrase), config('app.key'));
    }

    public static function normalizeMnemonic(string $phrase): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $phrase)));
    }
}
