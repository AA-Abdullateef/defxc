<?php

namespace App\Http\Requests\API\V1;

class DepositProofRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'deposit_photo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:1024',  // ~1000KB in kilobytes
            ],
        ];
    }
}
