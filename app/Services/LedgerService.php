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
     *   - pending + completed outgoing transfers
     */
    public function balanceFor(string $walletId, string $assetId): float
    {
        $deposited     = $this->sum($walletId, $assetId, TransactionType::Deposit->value,    [TransactionStatus::Completed->value]);
        $transfersIn   = $this->sum($walletId, $assetId, TransactionType::Transfer->value,   [TransactionStatus::Completed->value], 'incoming');
        $transfersOut  = $this->sum($walletId, $assetId, TransactionType::Transfer->value,   [TransactionStatus::Pending->value, TransactionStatus::Completed->value], 'outgoing');
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
        return Asset::active()
            ->get()
            ->map(fn (Asset $asset) => [
                'asset' => $asset,
                'deposits' => $this->platformSum($asset->id, TransactionType::Deposit->value, [TransactionStatus::Completed->value]),
                'withdrawals' => $this->platformSum($asset->id, TransactionType::Withdrawal->value, TransactionStatus::withdrawalDebitStatuses()),
                'transfers' => $this->platformSum($asset->id, TransactionType::Transfer->value, [TransactionStatus::Completed->value], 'outgoing'),
            ])
            ->filter(fn (array $total) =>
                $total['deposits'] != 0.0
                || $total['withdrawals'] != 0.0
                || $total['transfers'] != 0.0
            )
            ->values()
            ->all();
    }

    // ─── Private ─────────────────────────────────────────────────────────────────

    private function sum(string $walletId, string $assetId, string $type, array $statuses, ?string $direction = null): float
    {
        $query = Transaction::where('wallet_id', $walletId)
            ->where('asset_id', $assetId)
            ->where('type', $type)
            ->whereIn('status', $statuses);

        if ($direction !== null) {
            $query->where('meta->direction', $direction);
        }

        return (float) $query->sum('amount');
    }

    private function platformSum(string $assetId, string $type, array $statuses, ?string $direction = null): float
    {
        $query = Transaction::where('asset_id', $assetId)
            ->where('type', $type)
            ->whereIn('status', $statuses);

        if ($direction !== null) {
            $query->where('meta->direction', $direction);
        }

        return (float) $query->sum('amount');
    }
}
