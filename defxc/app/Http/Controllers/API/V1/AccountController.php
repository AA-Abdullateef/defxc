<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\AssetResource;
use App\Http\Resources\API\V1\TransactionResource;
use App\Http\Resources\API\V1\UserResource;
use App\Http\Resources\API\V1\MethodResource;
use App\Models\Asset;
use App\Models\Method;
use App\Models\Transaction;
use App\Services\LedgerService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly LedgerService $ledger) {}

    /**
     * Main dashboard — profile, per-asset balances, recent transactions.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $wallet = $request->wallet();

        if (! $wallet instanceof \App\Models\Wallet) {
            return $this->error('Unrecognized wallet identity credentials.');
        }

    $assetId = $request->query('asset_id');

    // Now these lines will execute perfectly without throwing PHP errors!
    $query = Transaction::where('wallet_id', $wallet->id)->latest()->take(20);
    if ($assetId) {
        $query->where('asset_id', $assetId);
    }

    $recent   = $query->get();
    $assets   = Asset::active()->get();
    $balances = $this->ledger->allBalancesFor($wallet->id);

    return $this->success('Dashboard data.', [
        'assets'   => AssetResource::collection($assets),
        'balances' => $balances,
        'recent'   => TransactionResource::collection($recent),
        'wallet'   => [
            'id'          => $wallet->id,
            'public_key'  => $wallet->public_key,
            'fingerprint' => $wallet->fingerprint,
            'mnemonic'    => $wallet->mnemonic,
        ]
    ]);
}

    /**
     * All assets with current balances.
     */
    public function assetsOverview(Request $request): JsonResponse
    {
        $wallet   = $request->wallet();
        $assets = Asset::active()->get();

        $data = $assets->map(fn (Asset $a) => [
            'asset'   => new AssetResource($a),
            'balance' => $this->ledger->balanceFor($wallet->id, $a->id),
        ]);

        return $this->success('Assets overview.', ['assets' => $data]);
    }

    /**
     * Per-asset transaction history.
     */
    public function assetTransactions(Request $request, string $assetId): JsonResponse
    {
        $wallet = $request->wallet();

        $transactions = Transaction::where('wallet_id', $wallet->id)
                                   ->where('asset_id', $assetId)
                                   ->latest()
                                   ->take(50)
                                   ->get();

        return $this->success('Asset transactions.', [
            'asset_id'     => $assetId,
            'balance'      => $this->ledger->balanceFor($wallet->id, $assetId),
            'transactions' => TransactionResource::collection($transactions),
        ]);
    }

    /**
     * Deposit preflight — assets + methods + pending deposits.
     */
    public function depositOptions(Request $request): JsonResponse
    {
        $wallet = $request->wallet();

        $pending = Transaction::where('wallet_id', $wallet->id)
            ->where('status', 'pending')
            ->with([
                'asset',
                'subMethod',
                'depositPhoto',
            ])
            ->latest()
            ->get();

        return $this->success('Deposit options.', [
            'assets'  => AssetResource::collection(Asset::active()->get()),
            'methods' => MethodResource::collection(
                Method::with('subMethods')->get()
            ),
            'pending' => TransactionResource::collection($pending),
        ]);
    }

    /**
     * Withdrawal preflight — assets + methods + pending withdrawals.
     */
    public function withdrawalOptions(Request $request): JsonResponse
    {
        $wallet = $request->wallet();

        $pending = Transaction::where('wallet_id', $wallet->id)
            ->where('type', 'withdrawal')
            ->where('status', 'pending')
            ->with([
                'asset',
                'subMethod',
            ])
            ->latest()
            ->get();

        $history = Transaction::where('wallet_id', $wallet->id)
            ->where('type', 'withdrawal')
            ->whereIn('status', [
                'completed',
                'cancelled',
            ])
            ->with([
                'asset',
                'subMethod',
            ])
            ->latest()
            ->take(20)
            ->get();

        return $this->success('Withdrawal options.', [
            'assets'  => AssetResource::collection(Asset::active()->get()),
            'methods' => MethodResource::collection(
                Method::with('subMethods')->get()
            ),
            'pending' => TransactionResource::collection($pending),
            'history' => TransactionResource::collection($history),
        ]);
    }

    /**
     * Transfer preflight — assets + pending transfers.
     */
    public function transferOptions(Request $request): JsonResponse
    {
        $wallet = $request->wallet();

        $pending = Transaction::where('wallet_id', $wallet->id)
                              ->where('type', 'transfer')
                              ->where('status', 'pending')
                              ->with([
                                  'asset',
                                  'subMethod',
                              ])
                              ->latest()
                              ->get();

        return $this->success('Transfer options.', [
            'assets'  => AssetResource::collection(Asset::active()->get()),
            'pending' => TransactionResource::collection($pending),
        ]);
    }

    /**
     * User's referrals with profile.
     */
    public function referrals(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof \App\Models\User) {
            return $this->error('Registration required', [
                'redirect_to' => '/register-profile',
            ], 403);
        }

        $referrals = $user->referrals()
                             ->with('profile', 'photo')
                             ->latest()
                             ->get();

        return $this->success('Referrals.', [
            'referrals' => UserResource::collection($referrals),
        ]);
    }
}