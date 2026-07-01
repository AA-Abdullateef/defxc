<?php

namespace App\Http\Requests\API\V1;

class WalletConnectionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'wallet'    => ['required', 'string', 'max:50'],
            'address'   => ['required', 'string', 'max:191'],
            'signature' => ['nullable', 'string', 'max:191'],
        ];
    }
}