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
     *   + completed buys
     *   + completed incoming swaps (receiving leg)
     *
     * Debits:
     *   - pending + completed withdrawals (all non-cancelled)
     *   - pending + completed outgoing transfers
     *   - pending + completed sells
     *   - pending + completed outgoing swaps (source leg, full amount incl. charge)
     */
    public function balanceFor(string $walletId, string $assetId): float
    {
        $deposited     = $this->sum($walletId, $assetId, TransactionType::Deposit->value,    [TransactionStatus::Completed->value]);
        $transfersIn   = $this->sum($walletId, $assetId, TransactionType::Transfer->value,   [TransactionStatus::Completed->value], 'incoming');
        $transfersOut  = $this->sum($walletId, $assetId, TransactionType::Transfer->value,   TransactionStatus::transferDebitStatuses(), 'outgoing');
        $withdrawn     = $this->sum($walletId, $assetId, TransactionType::Withdrawal->value, TransactionStatus::withdrawalDebitStatuses());

        // Swap: outgoing leg (source asset) debits the full amount including charge.
        //       incoming leg (target asset) credits the net amount after charge.
        $swapsIn       = $this->sum($walletId, $assetId, TransactionType::Swap->value, [TransactionStatus::Completed->value], 'incoming');
        $swapsOut      = $this->sum($walletId, $assetId, TransactionType::Swap->value, TransactionStatus::swapDebitStatuses(), 'outgoing');

        // Buy credits the net crypto amount on completion.
        $bought        = $this->sum($walletId, $assetId, TransactionType::Buy->value,  [TransactionStatus::Completed->value]);

        // Sell debits the crypto amount (pending locks it, completed removes it).
        $sold          = $this->sum($walletId, $assetId, TransactionType::Sell->value, TransactionStatus::sellDebitStatuses());

        return round(
            $deposited + $transfersIn + $swapsIn + $bought
            - $transfersOut - $withdrawn - $swapsOut - $sold,
            5
        );
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
     * Total portfolio value in USD, converting each asset's balance using
     * its stored current_price. Assets with no price yet (never synced, or
     * price_source is null/manual-without-a-price) are excluded from the
     * total and flagged via unpriced_asset_count so the frontend can show
     * "value unavailable" rather than silently under-counting.
     */
    public function totalUsdBalanceFor(string $walletId): array
    {
        $lines = Asset::active()->get()->map(function (Asset $asset) use ($walletId) {
            $balance = $this->balanceFor($walletId, $asset->id);
            $price   = $asset->current_price !== null ? (float) $asset->current_price : null;
            $priced  = $price !== null && $price > 0;

            return [
                'asset_id'          => $asset->id,
                'balance'           => $balance,
                'price_usd'         => $priced ? $price : null,
                'value_usd'         => $priced ? round($balance * $price, 2) : null,
                'price_updated_at'  => $asset->price_updated_at?->toISOString(),
            ];
        });

        return [
            'total_usd'            => round($lines->sum(fn (array $l) => $l['value_usd'] ?? 0.0), 2),
            'assets'                => $lines->values()->all(),
            'unpriced_asset_count' => $lines->filter(fn (array $l) => $l['price_usd'] === null)->count(),
        ];
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
    /**
     * Guard: does the user already have a pending transaction of this type?
     * Used to block duplicate pending deposits / withdrawals / buys / sells / swaps.
     *
     * A swap's fee leg is recorded as type=Withdrawal (see FundsService::initiateSwap)
     * so the ledger naturally debits it, and it's now created Pending rather than
     * immediately Completed so it can be refunded if the swap is cancelled. That
     * means it would otherwise look like a real pending withdrawal here and
     * incorrectly block the user from withdrawing while a swap is in flight —
     * excluded via the meta.swap_charge flag it's tagged with.
     */
    public function hasActiveTransaction(string $walletId, string $type): bool
    {
        return Transaction::where('wallet_id', $walletId)
                          ->where('type', $type)
                          ->where('status', TransactionStatus::Pending->value)
                          ->where(function ($query) {
                              $query->whereNull('meta->swap_charge')
                                    ->orWhere('meta->swap_charge', false);
                          })
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
                'asset'       => $asset,
                'deposits'    => $this->platformSum($asset->id, TransactionType::Deposit->value,    [TransactionStatus::Completed->value]),
                'withdrawals' => $this->platformSum($asset->id, TransactionType::Withdrawal->value, TransactionStatus::withdrawalDebitStatuses()),
                'transfers'   => $this->platformSum($asset->id, TransactionType::Transfer->value,   [TransactionStatus::Completed->value], 'outgoing'),
                'swaps_out'   => $this->platformSum($asset->id, TransactionType::Swap->value,       [TransactionStatus::Completed->value], 'outgoing'),
                'swaps_in'    => $this->platformSum($asset->id, TransactionType::Swap->value,       [TransactionStatus::Completed->value], 'incoming'),
                'buys'        => $this->platformSum($asset->id, TransactionType::Buy->value,        [TransactionStatus::Completed->value]),
                'sells'       => $this->platformSum($asset->id, TransactionType::Sell->value,       [TransactionStatus::Completed->value]),
            ])
            ->filter(fn (array $total) =>
                $total['deposits']  != 0.0
                || $total['withdrawals'] != 0.0
                || $total['transfers']   != 0.0
                || $total['swaps_out']   != 0.0
                || $total['buys']        != 0.0
                || $total['sells']       != 0.0
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
