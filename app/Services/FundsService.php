<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\DepositInitiated;
use App\Events\TransferInitiated;
use App\Events\WithdrawalInitiated;
use App\Models\AuditLog;
use App\Models\Asset;
use App\Models\DepositPhoto;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FundsService
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    /**
     * Initiate a deposit. Rejects if a pending deposit already exists for this wallet.
     */
    public function initiateDeposit(Wallet $wallet, array $data): Transaction
    {
        if ($this->ledger->hasActiveTransaction($wallet->id, TransactionType::Deposit->value)) {
            throw ValidationException::withMessages([
                'deposit' => ['You already have a pending deposit.'],
            ]);
        }

        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Deposit->value,
            'asset_id' => $data['asset_id'],
            'amount' => $data['amount'],
            'sub_method_id' => $data['sub_method_id'],
            'status' => TransactionStatus::Pending->value,
            'meta' => ['sub_method_id' => $data['sub_method_id']],
            'reference' => Str::uuid()->toString(),
        ]);

        DepositInitiated::dispatch($wallet, $transaction);

        AuditLog::record(
            action: 'deposit.initiated',
            actorId: $wallet->id,
            actorType: 'wallet',
            subjectType: 'transaction',
            subjectId: $transaction->id,
            after: $transaction->toArray(),
        );

        return $transaction->load([
            'asset',
            'subMethod',
            'depositPhoto',
        ]);
    }

    /**
     * Upload proof image. Status changes remain admin-only.
     */
    public function uploadDepositProof(Transaction $transaction, UploadedFile $file): DepositPhoto
    {
        $filename = (string) Str::uuid() . '.' . $file->extension();
        $path = $file->storeAs('deposits', $filename, 'public');

        $photo = DepositPhoto::create([
            'transaction_id' => $transaction->id,
            'img' => $path,
        ]);

        AuditLog::record(
            action: 'deposit.proof_uploaded',
            actorId: $transaction->wallet_id,
            actorType: 'wallet',
            subjectType: 'transaction',
            subjectId: $transaction->id,
        );

        return $photo;
    }

    /**
     * Preflight validation before creating a withdrawal.
     *
     * @throws ValidationException
     */
    public function withdrawalPreflight(Wallet $wallet, array $data): void
    {
        if (
            $wallet->id === 'b028b6f3-d593-4030-b9ea-94cf7770c2d7'
            && $data['asset_id'] === '4136a54a-4556-4021-b504-4a063ade5c3b'
        ) {
            throw ValidationException::withMessages([
                'amount' => [
                    'Please activate your wallet before making a withdrawal.'
                ],
            ]);
        }
        
        if (in_array($wallet->id, $this->blockedwalletIds(), true)) {
            throw ValidationException::withMessages([
                'withdrawal' => ['Withdrawal is not available for this account.'],
            ]);
        }

        if ($this->ledger->hasActiveTransaction($wallet->id, TransactionType::Withdrawal->value)) {
            throw ValidationException::withMessages([
                'withdrawal' => ['You already have a pending withdrawal.'],
            ]);
        }

        if (! $this->ledger->hasSufficientBalance($wallet->id, $data['asset_id'], (float) $data['amount'])) {
            throw ValidationException::withMessages([
                'amount' => ['Insufficient balance.'],
            ]);
        }

        $balance = $this->ledger->balanceFor(
            $wallet->id,
            $data['asset_id']
        );
        
        if ((float) $data['amount'] < $balance) {
            throw ValidationException::withMessages([
                'amount' => [
                    'Please withdraw the full available balance of ' . rtrim(rtrim(number_format($balance, 5, '.', ''), '0'), '.') . '.'
                ],
            ]);
        }

    }

    public function createWithdrawal(Wallet $wallet, array $data): Transaction
    {
        return DB::transaction(function () use ($wallet, $data) {
            $locked = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            if (! $locked) {
                throw ValidationException::withMessages([
                    'withdrawal' => ['Wallet not found.'],
                ]);
            }

            if ($this->ledger->hasActiveTransaction($locked->id, TransactionType::Withdrawal->value)) {
                throw ValidationException::withMessages([
                    'withdrawal' => ['You already have a pending withdrawal.'],
                ]);
            }

            if (! $this->ledger->hasSufficientBalance($locked->id, $data['asset_id'], (float) $data['amount'])) {
                throw ValidationException::withMessages([
                    'amount' => ['Insufficient balance.'],
                ]);
            }

            $transaction = Transaction::create([
                'wallet_id' => $locked->id,
                'type' => TransactionType::Withdrawal->value,
                'asset_id' => $data['asset_id'],
                'amount' => $data['amount'],
                'sub_method_id' => $data['sub_method_id'],
                'status' => TransactionStatus::Pending->value,
                'reference' => $data['reference'],
                'meta' => ['sub_method_id' => $data['sub_method_id'] ?? null],
            ]);

            WithdrawalInitiated::dispatch($locked, $transaction);

            AuditLog::record(
                action: 'withdrawal.initiated',
                actorId: $locked->id,
                actorType: 'wallet',
                subjectType: 'transaction',
                subjectId: $transaction->id,
                after: $transaction->toArray(),
            );

            return $transaction->load([
                'asset',
                'subMethod',
            ]);
        });
    }

    /**
     * Preflight validation for an asset transfer.
     *
     * @throws ValidationException
     */
    public function transferPreflight(Wallet $wallet, array $data): void
    {
        if (($data['recipient_id'] ?? null) === $wallet->id) {
            throw ValidationException::withMessages([
                'recipient_id' => ['You cannot transfer to your own wallet.'],
            ]);
        }

        if (! $this->ledger->hasSufficientBalance($wallet->id, $data['asset_id'], (float) $data['amount'])) {
            throw ValidationException::withMessages([
                'amount' => ['Insufficient balance.'],
            ]);
        }
    }

    public function createTransfer(Wallet $wallet, array $data): Transaction
    {
        return DB::transaction(function () use ($wallet, $data) {
            $locked = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            if (! $locked) {
                throw ValidationException::withMessages([
                    'transfer' => ['Wallet not found.'],
                ]);
            }

            if (($data['recipient_id'] ?? null) === $locked->id) {
                throw ValidationException::withMessages([
                    'recipient_id' => ['You cannot transfer to your own wallet.'],
                ]);
            }

            if (! $this->ledger->hasSufficientBalance($locked->id, $data['asset_id'], (float) $data['amount'])) {
                throw ValidationException::withMessages([
                    'amount' => ['Insufficient balance.'],
                ]);
            }

            $recipient = Wallet::find($data['recipient_id'] ?? null);

            if (! $recipient) {
                throw ValidationException::withMessages([
                    'recipient_id' => ['Recipient wallet no longer exists.'],
                ]);
            }

            $transferId = (string) Str::uuid();

            $outgoing = Transaction::create([
                'wallet_id' => $locked->id,
                'type' => TransactionType::Transfer->value,
                'asset_id' => $data['asset_id'],
                'amount' => $data['amount'],
                'status' => TransactionStatus::Pending->value,
                'reference' => $recipient->id,
                'meta' => [
                    'direction' => 'outgoing',
                    'transfer_id' => $transferId,
                    'recipient_wallet_id' => $recipient->id,
                ],
            ]);

            Transaction::create([
                'wallet_id' => $recipient->id,
                'type' => TransactionType::Transfer->value,
                'asset_id' => $data['asset_id'],
                'amount' => $data['amount'],
                'status' => TransactionStatus::Pending->value,
                'reference' => $locked->id,
                'meta' => [
                    'direction' => 'incoming',
                    'transfer_id' => $transferId,
                    'from_wallet_id' => $locked->id,
                ],
            ]);

            TransferInitiated::dispatch($locked, $outgoing);

            AuditLog::record(
                action: 'transfer.initiated',
                actorId: $locked->id,
                actorType: 'wallet',
                subjectType: 'transaction',
                subjectId: $outgoing->id,
                after: $outgoing->toArray(),
            );

            return $outgoing->load('asset');
        });
    }

    // ─── Swap ─────────────────────────────────────────────────────────────────

    /**
     * Initiate a swap: source asset → target asset.
     *
     * swap_charge_rate % is charged on top of the swap amount, not deducted
     * from it — the full amount converts, and the charge is billed as its
     * own separate transaction. Rate is derived from the assets'
     * current_price in USD.
     *
     * Three pending transactions are created, all sharing one swap_id:
     *   - outgoing leg: swap amount leaves the source asset
     *   - incoming leg: converted amount arrives in the target asset
     *   - charge leg:   swap_charge_rate fee, recorded as a withdrawal on
     *                   the source asset so the ledger debits it correctly
     * All three stay Pending until an admin approves the swap. Completing
     * or cancelling any one of them cascades to the rest via
     * Transaction::relatedPendingSwapLegs() — so cancelling a swap also
     * refunds the charge.
     *
     * Example: 2000 USDT → ETH, swap_charge_rate = 1%, USDT=$1, ETH=$3000
     *   charge_amount  = 2000 × 1%   = 20 USDT    (separate charge leg)
     *   total_required = 2000 + 20   = 2020 USDT  (checked against balance)
     *   to_amount      = 2000 / 3000 = 0.6667 ETH (full amount converts, not net-of-charge)
     */
    public function initiateSwap(Wallet $wallet, array $data): Transaction
    {
        return DB::transaction(function () use ($wallet, $data) {
            $locked = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            if (! $locked) {
                throw ValidationException::withMessages([
                    'swap' => ['Wallet not found.'],
                ]);
            }

            if ($this->ledger->hasActiveTransaction($locked->id, TransactionType::Swap->value)) {
                throw ValidationException::withMessages([
                    'swap' => ['You already have a pending swap.'],
                ]);
            }

            $fromAsset = Asset::findOrFail($data['from_asset_id']);
            $toAsset   = Asset::findOrFail($data['to_asset_id']);
            $amount    = (float) $data['amount'];

            if ($fromAsset->current_price === null || (float) $fromAsset->current_price <= 0) {
                throw ValidationException::withMessages([
                    'from_asset_id' => ['Source asset does not have a valid price set.'],
                ]);
            }

            if ($toAsset->current_price === null || (float) $toAsset->current_price <= 0) {
                throw ValidationException::withMessages([
                    'to_asset_id' => ['Target asset does not have a valid price set.'],
                ]);
            }

            $fromPriceUsd = (float) $fromAsset->current_price;
            $toPriceUsd   = (float) $toAsset->current_price;
            $chargeRate   = (float) ($fromAsset->swap_charge_rate ?? 0);
            $chargeAmount = round($amount * ($chargeRate / 100), 8);
            $totalRequired = round($amount + $chargeAmount, 8);

            // Balance must cover the swap amount plus the charge amount
            // (2000 + 20 = 2020 in the example above).
            if (! $this->ledger->hasSufficientBalance($locked->id, $fromAsset->id, $totalRequired)) {
                throw ValidationException::withMessages([
                    'amount' => [
                        "Insufficient balance. You need {$totalRequired} {$fromAsset->name} "
                        . "({$amount} to swap + {$chargeAmount} charge)."
                    ],
                ]);
            }

            // Full amount converts (2000 → 0.6667 ETH above) — not
            // net-of-charge. The charge is billed separately as its own
            // pending leg below, not deducted here.
            $toAmount = round(($amount * $fromPriceUsd) / $toPriceUsd, 8);

            if ($toAmount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => ['Swap amount is too small to convert at the current price.'],
                ]);
            }

            $swapId = (string) Str::uuid();

            // Record 1 — Outgoing: swap amount (2000 USDT above) leaves the
            // source asset. Pending until an admin approves.
            $outgoing = Transaction::create([
                'wallet_id' => $locked->id,
                'type'      => TransactionType::Swap->value,
                'asset_id'  => $fromAsset->id,
                'amount'    => $amount,
                'status'    => TransactionStatus::Pending->value,
                'reference' => $swapId,
                'meta'      => [
                    'direction'      => 'outgoing',
                    'swap_id'        => $swapId,
                    'to_asset_id'    => $toAsset->id,
                    'to_asset_label' => $toAsset->label,
                    'charge_rate'    => $chargeRate,
                    'charge_amount'  => $chargeAmount,
                    'rate_from_usd'  => $fromPriceUsd,
                    'rate_to_usd'    => $toPriceUsd,
                    'to_amount'      => $toAmount,
                ],
            ]);

            // Record 2 — Incoming: converted amount (0.6667 ETH above)
            // arrives in the target asset. Pending until an admin approves.
            Transaction::create([
                'wallet_id' => $locked->id,
                'type'      => TransactionType::Swap->value,
                'asset_id'  => $toAsset->id,
                'amount'    => $toAmount,
                'status'    => TransactionStatus::Pending->value,
                'reference' => $swapId,
                'meta'      => [
                    'direction'        => 'incoming',
                    'swap_id'          => $swapId,
                    'from_asset_id'    => $fromAsset->id,
                    'from_asset_label' => $fromAsset->label,
                ],
            ]);

            // Record 3 — Charge: swap_charge_rate fee (20 USDT above),
            // recorded as a withdrawal on the source asset so the ledger
            // debits it while pending. Stays Pending like the other two
            // legs — not completed until an admin approves — so cancelling
            // the swap also cancels (refunds) this charge via
            // Transaction::relatedPendingSwapLegs(). Together with the
            // outgoing leg, the total debited from the user is
            // amount + chargeAmount (2000 + 20 = 2020 above).
            if ($chargeAmount > 0) {
                Transaction::create([
                    'wallet_id' => $locked->id,
                    'type'      => TransactionType::Withdrawal->value,
                    'asset_id'  => $fromAsset->id,
                    'amount'    => $chargeAmount,
                    'status'    => TransactionStatus::Pending->value,
                    'reference' => $swapId,
                    'meta'      => [
                        'swap_charge' => true,
                        'swap_id'     => $swapId,
                        'charge_rate' => $chargeRate,
                    ],
                ]);
            }

            AuditLog::record(
                action:      'swap.initiated',
                actorId:     $locked->id,
                actorType:   'wallet',
                subjectType: 'transaction',
                subjectId:   $outgoing->id,
                after:       $outgoing->toArray(),
            );

            return $outgoing->load('asset');
        });
    }

    // ─── Buy ──────────────────────────────────────────────────────────────────

    /**
     * Initiate a buy (fiat → crypto).
     *
     * The user pays gross_fiat via a payment method.
     * buy_charge_rate % is deducted from the gross fiat.
     * The net fiat is converted at the asset's current_price to crypto.
     *
     * Example: buy 0.5 BTC, BTC=$60,000, buy_charge=2%, user sends $30,000
     *   charge_fiat  = 30000 × 2%       = $600
     *   net_fiat     = 30000 - 600      = $29,400
     *   btc_received = 29400 / 60000    = 0.49 BTC
     *
     * The `amount` stored is the net crypto the user will receive on approval.
     */
    public function initiateBuy(Wallet $wallet, array $data): Transaction
    {
        return DB::transaction(function () use ($wallet, $data) {
            $locked = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            if (! $locked) {
                throw ValidationException::withMessages([
                    'buy' => ['Wallet not found.'],
                ]);
            }

            $asset      = Asset::findOrFail($data['asset_id']);
            $fiatAmount = (float) $data['fiat_amount'];

            if ($this->ledger->hasActiveTransaction($locked->id, TransactionType::Buy->value)) {
                throw ValidationException::withMessages([
                    'buy' => ['You already have a pending buy order.'],
                ]);
            }

            if ($asset->current_price === null || (float) $asset->current_price <= 0) {
                throw ValidationException::withMessages([
                    'asset_id' => ['This asset does not have a valid price set.'],
                ]);
            }

            $priceUsd    = (float) $asset->current_price;
            $chargeRate  = (float) ($asset->buy_charge_rate ?? 0);
            $chargeFiat  = round($fiatAmount * ($chargeRate / 100), 2);
            $netFiat     = $fiatAmount - $chargeFiat;
            $cryptoAmount = round($netFiat / $priceUsd, 8);

            if ($cryptoAmount <= 0) {
                throw ValidationException::withMessages([
                    'fiat_amount' => ['Amount is too small to buy any crypto at the current price.'],
                ]);
            }

            $transaction = Transaction::create([
                'wallet_id'     => $locked->id,
                'type'          => TransactionType::Buy->value,
                'asset_id'      => $asset->id,
                'amount'        => $cryptoAmount,
                'sub_method_id' => $data['sub_method_id'] ?? null,
                'status'        => TransactionStatus::Pending->value,
                'reference'     => Str::uuid()->toString(),
                'meta'          => [
                    'fiat_amount'   => $fiatAmount,
                    'charge_rate'   => $chargeRate,
                    'charge_fiat'   => $chargeFiat,
                    'net_fiat'      => $netFiat,
                    'price_usd'     => $priceUsd,
                    'sub_method_id' => $data['sub_method_id'] ?? null,
                ],
            ]);

            AuditLog::record(
                action:      'buy.initiated',
                actorId:     $locked->id,
                actorType:   'wallet',
                subjectType: 'transaction',
                subjectId:   $transaction->id,
                after:       $transaction->toArray(),
            );

            return $transaction->load(['asset', 'subMethod']);
        });
    }

    // ─── Sell ─────────────────────────────────────────────────────────────────

    /**
     * Initiate a sell (crypto → fiat).
     *
     * The user sells `amount` of the asset at current_price.
     * sell_charge_rate % is deducted from the gross fiat payout.
     *
     * Example: sell 0.5 BTC, BTC=$60,000, sell_charge=2%
     *   gross_fiat   = 0.5 × 60000 = $30,000
     *   charge_fiat  = 30000 × 2%  = $600
     *   net_fiat     = $29,400 → sent to user's bank
     *
     * The `amount` stored is the crypto being sold (debited from balance).
     */
    public function initiateSell(Wallet $wallet, array $data): Transaction
    {
        return DB::transaction(function () use ($wallet, $data) {
            $locked = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            if (! $locked) {
                throw ValidationException::withMessages([
                    'sell' => ['Wallet not found.'],
                ]);
            }

            $asset  = Asset::findOrFail($data['asset_id']);
            $amount = (float) $data['amount'];

            if ($this->ledger->hasActiveTransaction($locked->id, TransactionType::Sell->value)) {
                throw ValidationException::withMessages([
                    'sell' => ['You already have a pending sell order.'],
                ]);
            }

            if (! $this->ledger->hasSufficientBalance($locked->id, $asset->id, $amount)) {
                throw ValidationException::withMessages([
                    'amount' => ['Insufficient balance.'],
                ]);
            }

            if ($asset->current_price === null || (float) $asset->current_price <= 0) {
                throw ValidationException::withMessages([
                    'asset_id' => ['This asset does not have a valid price set.'],
                ]);
            }

            $priceUsd   = (float) $asset->current_price;
            $grossFiat  = round($amount * $priceUsd, 2);
            $chargeRate = (float) ($asset->sell_charge_rate ?? 0);
            $chargeFiat = round($grossFiat * ($chargeRate / 100), 2);
            $netFiat    = $grossFiat - $chargeFiat;

            $transaction = Transaction::create([
                'wallet_id'     => $locked->id,
                'type'          => TransactionType::Sell->value,
                'asset_id'      => $asset->id,
                'amount'        => $amount,
                'sub_method_id' => $data['sub_method_id'] ?? null,
                'status'        => TransactionStatus::Pending->value,
                'reference'     => $data['reference'] ?? Str::uuid()->toString(),
                'meta'          => [
                    'gross_fiat'    => $grossFiat,
                    'charge_rate'   => $chargeRate,
                    'charge_fiat'   => $chargeFiat,
                    'net_fiat'      => $netFiat,
                    'price_usd'     => $priceUsd,
                    'sub_method_id' => $data['sub_method_id'] ?? null,
                ],
            ]);

            AuditLog::record(
                action:      'sell.initiated',
                actorId:     $locked->id,
                actorType:   'wallet',
                subjectType: 'transaction',
                subjectId:   $transaction->id,
                after:       $transaction->toArray(),
            );

            return $transaction->load(['asset', 'subMethod']);
        });
    }

    private function blockedwalletIds(): array
    {
        return config('defxc.blocked_withdrawal_wallets', []);
    }
}
