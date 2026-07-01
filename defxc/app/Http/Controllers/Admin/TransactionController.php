<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Services\NotificationService;
use Illuminate\Http\Request;

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

        $before = ['status'=>$transaction->status];

        $transaction->update([
            'status'=>TransactionStatus::Completed->value
        ]);

        $this->notificationService
            ->sendTransactionNotice(
                $transaction->loadMissing('wallet.user')->wallet,
                $transaction
            );

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

        $before = ['status'=>$transaction->status];

        $transaction->update([
            'status'=>TransactionStatus::Cancelled->value
        ]);

        $this->notificationService
            ->sendTransactionNotice(
                $transaction->loadMissing('wallet.user')->wallet,
                $transaction
            );

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
}
