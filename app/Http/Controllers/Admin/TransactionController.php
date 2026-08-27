<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function index(Request $request)
    {
        $query = Transaction::with([
            'wallet.user.profile',
            'asset',
            'subMethod',
            'depositPhoto'
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

            $query->whereHas('wallet.user', fn($q) =>
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
            );
        }

        return view(
            'admin.transactions.index',
            [
                'transactions' => $query
                    ->paginate(25)
                    ->withQueryString(),

                'assets' => Asset::active()->get()
            ]
        );
    }

    public function show(Transaction $transaction)
    {
        $transaction->load([
            'wallet.user.profile',
            'asset',
            'subMethod',
            'depositPhoto'
        ]);

        return view(
            'admin.transactions.show',
            compact('transaction')
        );
    }

    public function complete(Transaction $transaction)
    {
        if (! $transaction->isPending()) {
            return back()->with(
                'error',
                'Only pending transactions can be completed.'
            );
        }

        $before = ['status' => $transaction->status];
        $completedTransactions = [];

        DB::transaction(function () use ($transaction, &$completedTransactions): void {
            $transaction->update([
                'status' => TransactionStatus::Completed->value,
            ]);

            $completedTransactions[] = $transaction->fresh('wallet.user');

            $relatedTransfer = $this->relatedPendingTransfer($transaction);

            if ($relatedTransfer) {
                $relatedTransfer->update([
                    'status' => TransactionStatus::Completed->value,
                ]);

                $completedTransactions[] = $relatedTransfer->fresh('wallet.user');
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
            action:'transaction.completed',
            actorId:auth()->id(),
            actorType:'admin',
            subjectType:'transaction',
            subjectId:$transaction->id,
            before:$before,
            after:[
                'status'=>TransactionStatus::Completed->value
            ]
        );

        return back()
            ->with(
                'success',
                'Transaction completed.'
            );
    }

    public function cancel(Transaction $transaction)
    {
        if (! $transaction->isPending()) {
            return back()->with(
                'error',
                'Only pending transactions can be cancelled.'
            );
        }

        $before = ['status' => $transaction->status];
        $cancelledTransactions = [];

        DB::transaction(function () use ($transaction, &$cancelledTransactions): void {
            $transaction->update([
                'status' => TransactionStatus::Cancelled->value,
            ]);

            $cancelledTransactions[] = $transaction->fresh('wallet.user');

            $relatedTransfer = $this->relatedPendingTransfer($transaction);

            if ($relatedTransfer) {
                $relatedTransfer->update([
                    'status' => TransactionStatus::Cancelled->value,
                ]);

                $cancelledTransactions[] = $relatedTransfer->fresh('wallet.user');
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
            action:'transaction.cancelled',
            actorId:auth()->id(),
            actorType:'admin',
            subjectType:'transaction',
            subjectId:$transaction->id,
            before:$before,
            after:[
                'status'=>TransactionStatus::Cancelled->value
            ]
        );

        return back()
            ->with(
                'success',
                'Transaction cancelled.'
            );
    }

    private function relatedPendingTransfer(Transaction $transaction): ?Transaction
    {
        if ($transaction->type !== TransactionType::Transfer->value) {
            return null;
        }

        $direction = $transaction->meta['direction'] ?? null;

        if ($direction === 'outgoing') {
            return Transaction::query()
                ->where('type', TransactionType::Transfer->value)
                ->where('status', TransactionStatus::Pending->value)
                ->where('wallet_id', $transaction->reference)
                ->where('asset_id', $transaction->asset_id)
                ->where('amount', $transaction->amount)
                ->where('reference', $transaction->wallet_id)
                ->where('meta->direction', 'incoming')
                ->where('meta->from_wallet_id', $transaction->wallet_id)
                ->latest()
                ->first();
        }

        if ($direction === 'incoming') {
            $senderWalletId = $transaction->meta['from_wallet_id'] ?? $transaction->reference;

            return Transaction::query()
                ->where('type', TransactionType::Transfer->value)
                ->where('status', TransactionStatus::Pending->value)
                ->where('wallet_id', $senderWalletId)
                ->where('asset_id', $transaction->asset_id)
                ->where('amount', $transaction->amount)
                ->where('reference', $transaction->wallet_id)
                ->where('meta->direction', 'outgoing')
                ->latest()
                ->first();
        }

        return null;
    }
}
