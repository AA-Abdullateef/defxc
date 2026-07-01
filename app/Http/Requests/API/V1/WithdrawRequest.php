<?php

namespace App\Http\Requests\API\V1;

class WithdrawRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'asset_id'  => ['required', 'uuid', 'exists:assets,id'],
            'sub_method_id' => ['required', 'uuid', 'exists:sub_methods,id'],
            'reference' => ['required', 'string', 'min:9'],  // external wallet address
            'amount'    => ['required', 'numeric', 'gt:0'],
        ];
    }
}