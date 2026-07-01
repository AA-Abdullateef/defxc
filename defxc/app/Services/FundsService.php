<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\DepositInitiated;
use App\Events\TransferInitiated;
use App\Events\WithdrawalInitiated;
use App\Models\AuditLog;
use App\Models\DepositPhoto;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class FundsService
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly OtpService    $otpService,
    ) {}

    // ─── Deposits ────────────────────────────────────────────────────────────────

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
            'wallet_id'  => $wallet->id,
            'type'     => TransactionType::Deposit->value,
            'asset_id' => $data['asset_id'],
            'amount'   => $data['amount'],
            'sub_method_id' => $data['sub_method_id'],
            'status'   => TransactionStatus::Pending->value,
            'meta'     => ['sub_method_id' => $data['sub_method_id']],
            'reference'=> Str::uuid()->toString(),
        ]);

        DepositInitiated::dispatch($wallet, $transaction);

        AuditLog::record(
            action:      'deposit.initiated',
            actorId:     $wallet->id,
            actorType:   'wallet',
            subjectType: 'transaction',
            subjectId:   $transaction->id,
            after:       $transaction->toArray(),
        );

        return $transaction->load([
            'asset',
            'subMethod',
            'depositPhoto',
        ]);
    }

    /**
     * Upload proof image → sets deposit to 'completed' (admin reviews asynchronously).
     * The status stays 'pending' until an admin manually completes it.
     * Uploading proof only attaches the image — status change is admin-only.
     */
    public function uploadDepositProof(Transaction $transaction, UploadedFile $file): DepositPhoto
    {
        // Str::uuid() — consistent with the rest of the codebase; collision-resistant.
        // $file->extension() — MIME-derived, not client-filename-derived (safe).
        $filename = (string) Str::uuid() . '.' . $file->extension();

        $path = $file->storeAs('deposits', $filename, 'public');

        $photo = DepositPhoto::create([
            'transaction_id' => $transaction->id,
            'img'            => $path,
        ]);

        AuditLog::record(
            action:      'deposit.proof_uploaded',
            actorId:     $transaction->wallet_id,
            actorType:   'wallet',
            subjectType: 'transaction',
            subjectId:   $transaction->id,
        );

        return $photo;
    }

    // ─── Withdrawals ─────────────────────────────────────────────────────────────

    /**
     * Preflight validation before creating a withdrawal.
     *
     * @throws ValidationException
     */
    public function withdrawalPreflight(Wallet $wallet, array $data): void
    {
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
    }

    public function createWithdrawal(Wallet $wallet, array $data): Transaction
    {
        $transaction = Transaction::create([
            'wallet_id'   => $wallet->id,
            'type'      => TransactionType::Withdrawal->value,
            'asset_id'  => $data['asset_id'],
            'amount'    => $data['amount'],
            'sub_method_id' => $data['sub_method_id'],
            'status'    => TransactionStatus::Pending->value,
            'reference' => $data['reference'],          // external wallet address
            'meta'      => ['sub_method_id' => $data['sub_method_id'] ?? null],
        ]);

        WithdrawalInitiated::dispatch($wallet, $transaction);

        AuditLog::record(
            action:      'withdrawal.initiated',
            actorId:     $wallet->id,
            actorType:   'wallet',
            subjectType: 'transaction',
            subjectId:   $transaction->id,
            after:       $transaction->toArray(),
        );

        return $transaction->load([
            'asset',
            'subMethod',
        ]);
    }

    // ─── Transfers ───────────────────────────────────────────────────────────────

    /**
     * Preflight validation for an asset transfer.
     *
     * @throws ValidationException
     */
    public function transferPreflight(Wallet $wallet, array $data): void
    {
        if (! $this->ledger->hasSufficientBalance($wallet->id, $data['asset_id'], (float) $data['amount'])) {
            throw ValidationException::withMessages([
                'amount' => ['Insufficient balance.'],
            ]);
        }
    }

    public function createTransfer(Wallet $wallet, array $data): Transaction
    {
        $outgoing = Transaction::create([
            'wallet_id'   => $wallet->id,
            'type'      => TransactionType::Transfer->value,
            'asset_id'  => $data['asset_id'],
            'amount'    => $data['amount'],
            'status'    => TransactionStatus::Pending->value,
            'reference' => $data['recipient_id'] ?? null,
            'meta'      => ['direction' => 'outgoing'],
        ]);

        // Inter-account: create the incoming credit for the recipient immediately
        if (! empty($data['recipient_id'])) {
            $recipient = Wallet::find($data['recipient_id']);

            if ($recipient) {
                Transaction::create([
                    'wallet_id'   => $recipient->id,
                    'type'      => TransactionType::Transfer->value,
                    'asset_id'  => $data['asset_id'],
                    'amount'    => $data['amount'],
                    'status'    => TransactionStatus::Pending->value,
                    'reference' => $wallet->id,
                    'meta'      => ['direction' => 'incoming', 'from_wallet_id' => $wallet->id],
                ]);
            }
        }

        TransferInitiated::dispatch($wallet, $outgoing);

        AuditLog::record(
            action:      'transfer.initiated',
            actorId:     $wallet->id,
            actorType:   'wallet',
            subjectType: 'transaction',
            subjectId:   $outgoing->id,
            after:       $outgoing->toArray(),
        );

        return $outgoing->load('asset');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    private function blockedwalletIds(): array
    {
        return config('defxc.blocked_withdrawal_wallets', []);
    }
}