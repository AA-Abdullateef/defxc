<?php

namespace App\Http\Requests\API\V1;

class DepositRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'uuid', 'exists:assets,id'],
            'sub_method_id' => ['required', 'uuid', 'exists:sub_methods,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
