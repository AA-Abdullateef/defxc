<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Asset;
use App\Models\Transaction;

class LedgerService
{
    /**
     * Canonical balance formula for a user's asset.
     *
     * Credits:
     *   + completed deposits
     *   + completed incoming transfers
     *
     * Debits:
     *   - pending + completed withdrawals (all non-cancelled)
     *   - pending outgoing transfers
     */
    public function balanceFor(string $walletId, string $assetId): float
    {
        $deposited     = $this->sum($walletId, $assetId, TransactionType::Deposit->value,    [TransactionStatus::Completed->value]);
        $transfersIn   = $this->sum($walletId, $assetId, TransactionType::Transfer->value,   [TransactionStatus::Completed->value]);
        $transfersOut  = $this->sum($walletId, $assetId, TransactionType::Transfer->value,   [TransactionStatus::Pending->value]);
        $withdrawn     = $this->sum($walletId, $assetId, TransactionType::Withdrawal->value, TransactionStatus::withdrawalDebitStatuses());

        return round($deposited + $transfersIn - $transfersOut - $withdrawn, 5);
    }

    /**
     * Balance for every active asset the platform supports.
     * Returns ['asset_uuid' => float, ...]
     */
    public function allBalancesFor(string $walletId): array
    {
        return Asset::active()
                    ->get()
                    ->mapWithKeys(fn (Asset $asset) => [
                        $asset->id => $this->balanceFor($walletId, $asset->id),
                    ])
                    ->all();
    }

    /**
     * Guard: does the user have enough balance to debit this asset?
     */
    public function hasSufficientBalance(string $walletId, string $assetId, float $amount): bool
    {
        return $this->balanceFor($walletId, $assetId) >= $amount;
    }

    /**
     * Guard: does the user already have a pending transaction of this type?
     * Used to block duplicate pending deposits / withdrawals.
     */
    public function hasActiveTransaction(string $walletId, string $type): bool
    {
        return Transaction::where('wallet_id', $walletId)
                          ->where('type', $type)
                          ->where('status', TransactionStatus::Pending->value)
                          ->exists();
    }

    /**
     * Platform-wide totals for the admin dashboard.
     */
    public function platformTotals(): array
    {
        return [
            'total_deposits'    => (float) Transaction::where('type', TransactionType::Deposit->value)
                                                      ->where('status', TransactionStatus::Completed->value)
                                                      ->sum('amount'),

            'total_withdrawals' => (float) Transaction::where('type', TransactionType::Withdrawal->value)
                                                      ->whereIn('status', TransactionStatus::withdrawalDebitStatuses())
                                                      ->sum('amount'),

            'total_transfers'   => (float) Transaction::where('type', TransactionType::Transfer->value)
                                                      ->where('status', TransactionStatus::Completed->value)
                                                      ->sum('amount'),
        ];
    }

    // ─── Private ─────────────────────────────────────────────────────────────────

    private function sum(string $walletId, string $assetId, string $type, array $statuses): float
    {
        return (float) Transaction::where('wallet_id', $walletId)
                                  ->where('asset_id', $assetId)
                                  ->where('type', $type)
                                  ->whereIn('status', $statuses)
                                  ->sum('amount');
    }
}