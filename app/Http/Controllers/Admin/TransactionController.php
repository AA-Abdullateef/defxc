<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\DepositInitiated;
use App\Events\TransferInitiated;
use App\Events\WithdrawalInitiated;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Method;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\LedgerService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly LedgerService $ledger,
    ) {}

    public function index(Request $request)
    {
        $query = Transaction::with([
            'wallet.user.profile',
            'asset',
            'subMethod',
            'depositPhoto',
        ])->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->whereHas('wallet.user', fn ($q) =>
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
            );
        }

        return view('admin.transactions.index', [
            'transactions' => $query->paginate(25)->withQueryString(),
            'assets' => Asset::active()->get(),
        ]);
    }

    public function create()
    {
        return view('admin.transactions.create', [
            'wallets'       => Wallet::with('user')->latest()->get(),
            'assets'        => Asset::active()->get(),
            'cryptoMethods' => Method::crypto()->with('subMethods')->get(),
            'fiatMethods'   => Method::fiat()->with('subMethods')->get(),
        ]);
    }

    /**
     * Admin-authored transaction, any type. Skips the "already have a pending
     * X" duplicate-request guard from FundsService, since an admin recording
     * several transactions for one user isn't simulating a user request.
     * Balance IS enforced for every debit type (withdrawal, transfer, sell,
     * swap — buy and deposit are credit-only so there's nothing to check),
     * using the same LedgerService::hasSufficientBalance + row-locked wallet
     * pattern as FundsService, so a wallet can't be pushed negative here any
     * more than through the normal user-facing flow. The meta/leg shape for
     * each type still mirrors FundsService exactly so the ledger
     * (LedgerService::balanceFor) and the existing complete()/cancel()
     * cascade logic keep working the same regardless of how a transaction
     * was created.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'wallet_id'            => ['required', 'uuid', 'exists:wallets,id'],
            'type'                 => ['required', Rule::in(array_column(TransactionType::cases(), 'value'))],
            'status'               => ['required', Rule::in([TransactionStatus::Pending->value, TransactionStatus::Completed->value])],
            'asset_id'             => ['required', 'uuid', 'exists:assets,id'],
            'amount'               => ['nullable', 'numeric', 'min:0.00000001', 'required_unless:type,buy'],
            'fiat_amount'          => ['nullable', 'numeric', 'min:0.01', 'required_if:type,buy'],
            'sub_method_id'        => ['nullable', 'uuid', 'exists:sub_methods,id'],
            'recipient_wallet_id'  => ['nullable', 'uuid', 'exists:wallets,id', 'different:wallet_id', 'required_if:type,transfer'],
            'to_asset_id'          => ['nullable', 'uuid', 'exists:assets,id', 'different:asset_id', 'required_if:type,swap'],
            'reference'            => ['nullable', 'string', 'max:255'],
        ]);

        $wallet = Wallet::findOrFail($data['wallet_id']);

        $type = TransactionType::from($data['type']);

        try {
            $transaction = match ($type) {
                TransactionType::Deposit, TransactionType::Withdrawal
                    => $this->adminCreateDepositOrWithdrawal($wallet, $data, $type),
                TransactionType::Buy    => $this->adminCreateBuy($wallet, $data),
                TransactionType::Sell   => $this->adminCreateSell($wallet, $data),
                TransactionType::Transfer => $this->adminCreateTransfer($wallet, $data),
                TransactionType::Swap     => $this->adminCreateSwap($wallet, $data),
            };
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AuditLog::record(
            action: 'transaction.admin_created',
            actorId: auth()->id(),
            actorType: 'admin',
            subjectType: 'transaction',
            subjectId: $transaction->id,
            after: $transaction->toArray(),
        );

        return redirect()->route('admin.transactions.show', $transaction)
                         ->with('success', ucfirst($type->value) . ' transaction created.');
    }

    private function adminCreateDepositOrWithdrawal(Wallet $wallet, array $data, TransactionType $type): Transaction
    {
        return DB::transaction(function () use ($wallet, $data, $type) {
            $locked = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            if ($type === TransactionType::Withdrawal
                && ! $this->ledger->hasSufficientBalance($locked->id, $data['asset_id'], (float) $data['amount'])
            ) {
                throw new \RuntimeException('Insufficient balance.');
            }

            $transaction = Transaction::create([
                'wallet_id'     => $locked->id,
                'type'          => $type->value,
                'asset_id'      => $data['asset_id'],
                'amount'        => $data['amount'],
                'sub_method_id' => $data['sub_method_id'] ?? null,
                'status'        => $data['status'],
                'reference'     => $data['reference'] ?: (string) Str::uuid(),
                'meta'          => ['sub_method_id' => $data['sub_method_id'] ?? null, 'created_by_admin' => true],
            ]);

            if ($type === TransactionType::Deposit) {
                DepositInitiated::dispatch($locked, $transaction);
            } else {
                WithdrawalInitiated::dispatch($locked, $transaction);
            }

            if ($data['status'] === TransactionStatus::Completed->value) {
                $this->notificationService->sendTransactionNotice($locked, $transaction);
            }

            return $transaction->load(['asset', 'subMethod']);
        });
    }

    private function adminCreateBuy(Wallet $wallet, array $data): Transaction
    {
        $asset      = Asset::findOrFail($data['asset_id']);
        $fiatAmount = (float) $data['fiat_amount'];
        $priceUsd   = (float) ($asset->current_price ?? 0);

        if ($priceUsd <= 0) {
            throw new \RuntimeException('This asset does not have a valid price set.');
        }

        $chargeRate   = (float) ($asset->buy_charge_rate ?? 0);
        $chargeFiat   = round($fiatAmount * ($chargeRate / 100), 2);
        $netFiat      = $fiatAmount - $chargeFiat;
        $cryptoAmount = round($netFiat / $priceUsd, 8);

        $transaction = Transaction::create([
            'wallet_id'     => $wallet->id,
            'type'          => TransactionType::Buy->value,
            'asset_id'      => $asset->id,
            'amount'        => $cryptoAmount,
            'sub_method_id' => $data['sub_method_id'] ?? null,
            'status'        => $data['status'],
            'reference'     => $data['reference'] ?: (string) Str::uuid(),
            'meta'          => [
                'fiat_amount' => $fiatAmount, 'charge_rate' => $chargeRate, 'charge_fiat' => $chargeFiat,
                'net_fiat' => $netFiat, 'price_usd' => $priceUsd, 'sub_method_id' => $data['sub_method_id'] ?? null,
                'created_by_admin' => true,
            ],
        ]);

        if ($data['status'] === TransactionStatus::Completed->value) {
            $this->notificationService->sendTransactionNotice($wallet, $transaction);
        }

        return $transaction->load(['asset', 'subMethod']);
    }

    private function adminCreateSell(Wallet $wallet, array $data): Transaction
    {
        $asset    = Asset::findOrFail($data['asset_id']);
        $amount   = (float) $data['amount'];
        $priceUsd = (float) ($asset->current_price ?? 0);

        if ($priceUsd <= 0) {
            throw new \RuntimeException('This asset does not have a valid price set.');
        }

        return DB::transaction(function () use ($wallet, $data, $asset, $amount, $priceUsd) {
            $locked = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            if (! $this->ledger->hasSufficientBalance($locked->id, $asset->id, $amount)) {
                throw new \RuntimeException('Insufficient balance.');
            }

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
                'status'        => $data['status'],
                'reference'     => $data['reference'] ?: (string) Str::uuid(),
                'meta'          => [
                    'gross_fiat' => $grossFiat, 'charge_rate' => $chargeRate, 'charge_fiat' => $chargeFiat,
                    'net_fiat' => $netFiat, 'price_usd' => $priceUsd, 'sub_method_id' => $data['sub_method_id'] ?? null,
                    'created_by_admin' => true,
                ],
            ]);

            if ($data['status'] === TransactionStatus::Completed->value) {
                $this->notificationService->sendTransactionNotice($locked, $transaction);
            }

            return $transaction->load(['asset', 'subMethod']);
        });
    }

    private function adminCreateTransfer(Wallet $wallet, array $data): Transaction
    {
        $recipientWallet = Wallet::findOrFail($data['recipient_wallet_id']);

        return DB::transaction(function () use ($wallet, $recipientWallet, $data) {
            $locked = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            if (! $this->ledger->hasSufficientBalance($locked->id, $data['asset_id'], (float) $data['amount'])) {
                throw new \RuntimeException('Insufficient balance.');
            }

            $transferId = (string) Str::uuid();

            $outgoing = Transaction::create([
                'wallet_id' => $locked->id,
                'type'      => TransactionType::Transfer->value,
                'asset_id'  => $data['asset_id'],
                'amount'    => $data['amount'],
                'status'    => $data['status'],
                'reference' => $recipientWallet->id,
                'meta'      => [
                    'direction' => 'outgoing', 'transfer_id' => $transferId,
                    'recipient_wallet_id' => $recipientWallet->id, 'created_by_admin' => true,
                ],
            ]);

            $incoming = Transaction::create([
                'wallet_id' => $recipientWallet->id,
                'type'      => TransactionType::Transfer->value,
                'asset_id'  => $data['asset_id'],
                'amount'    => $data['amount'],
                'status'    => $data['status'],
                'reference' => $locked->id,
                'meta'      => [
                    'direction' => 'incoming', 'transfer_id' => $transferId,
                    'from_wallet_id' => $locked->id, 'created_by_admin' => true,
                ],
            ]);

            TransferInitiated::dispatch($locked, $outgoing);

            if ($data['status'] === TransactionStatus::Completed->value) {
                $this->notificationService->sendTransactionNotice($locked, $outgoing);
                $this->notificationService->sendTransactionNotice($recipientWallet, $incoming);
            }

            return $outgoing->load('asset');
        });
    }

    private function adminCreateSwap(Wallet $wallet, array $data): Transaction
    {
        $fromAsset = Asset::findOrFail($data['asset_id']);
        $toAsset   = Asset::findOrFail($data['to_asset_id']);
        $amount    = (float) $data['amount'];

        $fromPriceUsd = (float) ($fromAsset->current_price ?? 0);
        $toPriceUsd   = (float) ($toAsset->current_price ?? 0);

        if ($fromPriceUsd <= 0) {
            throw new \RuntimeException('Source asset does not have a valid price set.');
        }

        if ($toPriceUsd <= 0) {
            throw new \RuntimeException('Target asset does not have a valid price set.');
        }

        $chargeRate   = (float) ($fromAsset->swap_charge_rate ?? 0);
        $chargeAmount = round($amount * ($chargeRate / 100), 8);
        $toAmount     = round(($amount * $fromPriceUsd) / $toPriceUsd, 8);

        if ($toAmount <= 0) {
            throw new \RuntimeException('Swap amount is too small to convert at the current price.');
        }

        return DB::transaction(function () use ($wallet, $data, $fromAsset, $toAsset, $amount, $fromPriceUsd, $toPriceUsd, $chargeRate, $chargeAmount, $toAmount) {
            $locked = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            $totalRequired = round($amount + $chargeAmount, 8);

            if (! $this->ledger->hasSufficientBalance($locked->id, $fromAsset->id, $totalRequired)) {
                throw new \RuntimeException(
                    "Insufficient balance. You need {$totalRequired} {$fromAsset->name} "
                    . "({$amount} to swap + {$chargeAmount} charge)."
                );
            }

            $swapId = (string) Str::uuid();

            $outgoing = Transaction::create([
                'wallet_id' => $locked->id,
                'type'      => TransactionType::Swap->value,
                'asset_id'  => $fromAsset->id,
                'amount'    => $amount,
                'status'    => $data['status'],
                'reference' => $swapId,
                'meta'      => [
                    'direction' => 'outgoing', 'swap_id' => $swapId,
                    'to_asset_id' => $toAsset->id, 'to_asset_label' => $toAsset->label,
                    'charge_rate' => $chargeRate, 'charge_amount' => $chargeAmount,
                    'rate_from_usd' => $fromPriceUsd, 'rate_to_usd' => $toPriceUsd, 'to_amount' => $toAmount,
                    'created_by_admin' => true,
                ],
            ]);

            Transaction::create([
                'wallet_id' => $locked->id,
                'type'      => TransactionType::Swap->value,
                'asset_id'  => $toAsset->id,
                'amount'    => $toAmount,
                'status'    => $data['status'],
                'reference' => $swapId,
                'meta'      => [
                    'direction' => 'incoming', 'swap_id' => $swapId,
                    'from_asset_id' => $fromAsset->id, 'from_asset_label' => $fromAsset->label,
                    'created_by_admin' => true,
                ],
            ]);

            if ($chargeAmount > 0) {
                Transaction::create([
                    'wallet_id' => $locked->id,
                    'type'      => TransactionType::Withdrawal->value,
                    'asset_id'  => $fromAsset->id,
                    'amount'    => $chargeAmount,
                    'status'    => $data['status'],
                    'reference' => $swapId,
                    'meta'      => ['swap_charge' => true, 'swap_id' => $swapId, 'charge_rate' => $chargeRate, 'created_by_admin' => true],
                ]);
            }

            if ($data['status'] === TransactionStatus::Completed->value) {
                $this->notificationService->sendTransactionNotice($locked, $outgoing);
            }

            return $outgoing->load('asset');
        });
    }

    public function show(Transaction $transaction)
    {
        $transaction->load([
            'wallet.user.profile',
            'asset',
            'subMethod',
            'depositPhoto',
        ]);

        return view('admin.transactions.show', compact('transaction'));
    }

    public function complete(Transaction $transaction)
    {
        if (! $transaction->isPending()) {
            return back()->with('error', 'Only pending transactions can be completed.');
        }

        $before = ['status' => $transaction->status];
        $completedTransactions = [];

        DB::transaction(function () use ($transaction, &$completedTransactions): void {
            $transaction->update([
                'status' => TransactionStatus::Completed->value,
            ]);

            $completedTransactions[] = $transaction->fresh('wallet.user');

            // Handle the linked leg for transfers (cross-wallet, same asset).
            $relatedTransfer = $transaction->relatedPendingTransferLeg();

            if ($relatedTransfer) {
                $relatedTransfer->update([
                    'status' => TransactionStatus::Completed->value,
                ]);

                $completedTransactions[] = $relatedTransfer->fresh('wallet.user');
            }

            // Handle the linked legs for swaps (same wallet, cross-asset,
            // plus the fee leg) — all share meta->swap_id.
            foreach ($transaction->relatedPendingSwapLegs() as $relatedSwapLeg) {
                $relatedSwapLeg->update([
                    'status' => TransactionStatus::Completed->value,
                ]);

                $completedTransactions[] = $relatedSwapLeg->fresh('wallet.user');
            }
        });

        foreach ($completedTransactions as $completedTransaction) {
            if ($completedTransaction->wallet) {
                $this->notificationService->sendTransactionNotice(
                    $completedTransaction->wallet,
                    $completedTransaction
                );
            }
        }

        AuditLog::record(
            action: 'transaction.completed',
            actorId: auth()->id(),
            actorType: 'admin',
            subjectType: 'transaction',
            subjectId: $transaction->id,
            before: $before,
            after: ['status' => TransactionStatus::Completed->value],
        );

        return back()->with('success', 'Transaction completed.');
    }

    public function cancel(Transaction $transaction)
    {
        if (! $transaction->isPending()) {
            return back()->with('error', 'Only pending transactions can be cancelled.');
        }

        $before = ['status' => $transaction->status];
        $cancelledTransactions = [];

        DB::transaction(function () use ($transaction, &$cancelledTransactions): void {
            $transaction->update([
                'status' => TransactionStatus::Cancelled->value,
            ]);

            $cancelledTransactions[] = $transaction->fresh('wallet.user');

            // Handle the linked leg for transfers.
            $relatedTransfer = $transaction->relatedPendingTransferLeg();

            if ($relatedTransfer) {
                $relatedTransfer->update([
                    'status' => TransactionStatus::Cancelled->value,
                ]);

                $cancelledTransactions[] = $relatedTransfer->fresh('wallet.user');
            }

            // Handle the linked legs for swaps, including the fee leg — this
            // is what refunds the swap fee when a swap is cancelled.
            foreach ($transaction->relatedPendingSwapLegs() as $relatedSwapLeg) {
                $relatedSwapLeg->update([
                    'status' => TransactionStatus::Cancelled->value,
                ]);

                $cancelledTransactions[] = $relatedSwapLeg->fresh('wallet.user');
            }
        });

        foreach ($cancelledTransactions as $cancelledTransaction) {
            if ($cancelledTransaction->wallet) {
                $this->notificationService->sendTransactionNotice(
                    $cancelledTransaction->wallet,
                    $cancelledTransaction
                );
            }
        }

        AuditLog::record(
            action: 'transaction.cancelled',
            actorId: auth()->id(),
            actorType: 'admin',
            subjectType: 'transaction',
            subjectId: $transaction->id,
            before: $before,
            after: ['status' => TransactionStatus::Cancelled->value],
        );

        return back()->with('success', 'Transaction cancelled.');
    }
}
