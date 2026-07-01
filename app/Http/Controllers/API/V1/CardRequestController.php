<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\CardRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CardRequestController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $requests = CardRequest::where('wallet_id', $request->wallet()->id)
            ->latest()
            ->paginate(20);

        return $this->paginated('Card requests retrieved.', $requests);
    }

    public function store(Request $request): JsonResponse
    {
        $wallet = $request->wallet();

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'credit_score' => ['required', 'string', 'max:10'],
            'type' => ['required', 'string', 'max:100'],
            'img_one' => ['nullable', 'image', 'max:5120'],
            'img_two' => ['nullable', 'image', 'max:5120'],
        ]);

        $existing = CardRequest::where('wallet_id', $wallet->id)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return $this->error(
                'You already have a pending card request.'
            );
        }

        $imgOne = null;

        if ($request->hasFile('img_one')) {
            $file     = $request->file('img_one');
            $filename = $wallet->id . '_1_' . time() . '.' . $file->extension();
            $imgOne   = $file->storeAs('card-requests', $filename, 'public');
        }

        $imgTwo = null;

        if ($request->hasFile('img_two')) {
            $file     = $request->file('img_two');
            $filename = $wallet->id . '_2_' . time() . '.' . $file->extension();
            $imgTwo   = $file->storeAs('card-requests', $filename, 'public');
        }

        $cardRequest = CardRequest::create([
            'id' => (string) Str::uuid(),
            'wallet_id' => $wallet->id,
            'amount' => $data['amount'],
            'credit_score' => $data['credit_score'],
            'type' => $data['type'],
            'img_one' => $imgOne,
            'img_two' => $imgTwo,
            'status' => 'pending',
        ]);

        return $this->success(
            'Card request submitted.',
            $cardRequest
        );
    }

    public function show(Request $request, CardRequest $cardRequest): JsonResponse
    {
        if ($cardRequest->wallet_id !== $request->wallet()->id) {
            return $this->forbidden();
        }

        return $this->success(
            'Card request retrieved.',
            $cardRequest
        );
    }
}