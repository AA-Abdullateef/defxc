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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FundsService
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly OtpService $otpService,
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

    private function blockedwalletIds(): array
    {
        return config('defxc.blocked_withdrawal_wallets', []);
    }
}
