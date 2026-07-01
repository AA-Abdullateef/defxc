<?php

namespace App\Http\Requests\API\V1;

class TransferRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'asset_id'     => ['required', 'uuid', 'exists:assets,id'],
            'amount'       => ['required', 'numeric', 'min:0.1'],
            'recipient_id' => ['nullable', 'uuid', 'exists:wallets,id'],
        ];
    }
}