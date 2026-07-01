<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\TransactionResource;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponse;

    /**
     * Paginated list of the authenticated wallet's transactions.
     * Optionally filter by type: deposit | withdrawal | transfer
     */
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::where('wallet_id', $request->wallet()->id)->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }

        $perPage      = min((int) ($request->per_page ?? 15), 100);
        $transactions = $query->paginate($perPage);

        return $this->paginated(
            'Transactions.',
            $transactions,
            fn ($t) => new TransactionResource($t)
        );
    }

    /**
     * wallet-permitted status update.
     * wallets may only cancel (status=cancelled) their own pending transactions.
     * Any other status change is admin-only.
     */
    public function updateStatus(Request $request, Transaction $transaction): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:cancelled'],
        ]);

        if ($transaction->wallet_id !== $request->wallet()->id) {
            return $this->forbidden();
        }

        if (! $transaction->isPending()) {
            return $this->error('Only pending transactions can be cancelled.', null, 422);
        }

        $transaction->update(['status' => 'cancelled']);

        return $this->success('Transaction cancelled.', [
            'transaction' => new TransactionResource($transaction->fresh()),
        ]);
    }
}