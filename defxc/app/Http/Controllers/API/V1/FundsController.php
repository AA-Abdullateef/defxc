<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\DepositProofRequest;
use App\Http\Requests\API\V1\DepositRequest;
use App\Http\Requests\API\V1\TransferRequest;
use App\Http\Requests\API\V1\WithdrawRequest;
use App\Http\Resources\API\V1\TransactionResource;
use App\Models\Transaction;
use App\Services\FundsService;
use App\Services\NotificationService;
use App\Services\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FundsController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FundsService        $fundsService,
        private readonly OtpService          $otpService,
        private readonly NotificationService $notificationService,
    ) {}

    // ─── Deposits ────────────────────────────────────────────────────────────────

    public function initiateDeposit(DepositRequest $request): JsonResponse
    {
        try {
            $transaction = $this->fundsService->initiateDeposit(
                $request->wallet(),
                $request->validated()
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), $e->errors(), 422);
        }

        return $this->success('Deposit initiated.', [
            'transaction' => new TransactionResource($transaction),
        ], 201);
    }

    public function showDeposit(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->wallet_id !== $request->wallet()->id) {
            return $this->forbidden();
        }

        return $this->success('Deposit details.', [
            'transaction' => new TransactionResource($transaction->load('asset', 'subMethod', 'depositPhoto')),
        ]);
    }

    public function uploadDepositProof(DepositProofRequest $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->wallet_id !== $request->wallet()->id) {
            return $this->forbidden();
        }

        if ($transaction->type !== 'deposit' || ! $transaction->isPending()) {
            return $this->error('Only pending deposits can have proof uploaded.', null, 422);
        }

        $this->fundsService->uploadDepositProof($transaction, $request->file('deposit_photo'));

        return $this->success('Proof uploaded. Your deposit is under review.', [
            'transaction' => new TransactionResource($transaction->fresh('depositPhoto')),
        ]);
    }

    // ─── Withdrawals ─────────────────────────────────────────────────────────────

    public function initiateWithdrawal(WithdrawRequest $request): JsonResponse
    {
        $wallet = $request->wallet();
        $data = $request->validated();

        try {
            $this->fundsService->withdrawalPreflight($wallet, $data);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), $e->errors(), 422);
        }

        if ($wallet->user?->two_factor) {
            $token = $this->otpService->createTwoFactorToken($wallet->user, 'two_factor');
            $this->notificationService->sendOtp($wallet->user, $token, 'withdrawal');

            return $this->success('A confirmation code has been sent to your email.', [
                'requires_confirmation' => true,
            ]);
        }

        $transaction = $this->fundsService->createWithdrawal($wallet, $data);

        return $this->success('Withdrawal initiated.', [
            'transaction' => new TransactionResource($transaction),
        ], 201);
    }

    public function confirmWithdrawal(WithdrawRequest $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        $wallet = $request->wallet();
        $data = $request->validated();

        try {
            $this->fundsService->withdrawalPreflight($wallet, $data);
            if (! $wallet->user) {
                throw new \RuntimeException('A registered profile is required for two-factor confirmation.');
            }

            $this->otpService->verifyTwoFactorToken($wallet->user, $request->token, 'two_factor');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), $e->errors(), 422);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        $transaction = $this->fundsService->createWithdrawal($wallet, $data);

        return $this->success('Withdrawal initiated.', [
            'transaction' => new TransactionResource($transaction),
        ], 201);
    }

    // ─── Transfers ───────────────────────────────────────────────────────────────

    public function initiateTransfer(TransferRequest $request): JsonResponse
    {
        $wallet = $request->wallet();
        $data = $request->validated();

        try {
            $this->fundsService->transferPreflight($wallet, $data);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), $e->errors(), 422);
        }

        if ($wallet->user?->two_factor) {
            $token = $this->otpService->createTwoFactorToken($wallet->user, 'two_factor');
            $this->notificationService->sendOtp($wallet->user, $token, 'transfer');

            return $this->success('A confirmation code has been sent to your email.', [
                'requires_confirmation' => true,
            ]);
        }

        $transaction = $this->fundsService->createTransfer($wallet, $data);

        return $this->success('Transfer initiated.', [
            'transaction' => new TransactionResource($transaction),
        ], 201);
    }

    public function confirmTransfer(TransferRequest $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        $wallet = $request->wallet();
        $data = $request->validated();

        try {
            $this->fundsService->transferPreflight($wallet, $data);
            if (! $wallet->user) {
                throw new \RuntimeException('A registered profile is required for two-factor confirmation.');
            }

            $this->otpService->verifyTwoFactorToken($wallet->user, $request->token, 'two_factor');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), $e->errors(), 422);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        $transaction = $this->fundsService->createTransfer($wallet, $data);

        return $this->success('Transfer initiated.', [
            'transaction' => new TransactionResource($transaction),
        ], 201);
    }
}
