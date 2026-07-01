<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletConnection extends Model
{
    use HasUuid;

    protected $fillable = ['wallet_id', 'wallet', 'address', 'details', 'signature'];

    protected $hidden = ['details']; // prevent accidental serialisation of legacy field

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}