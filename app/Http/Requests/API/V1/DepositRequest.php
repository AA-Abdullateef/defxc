<?php

namespace App\Http\Requests\API\V1;

use App\Models\Method;
use Illuminate\Validation\Rule;

class DepositRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'uuid', 'exists:assets,id'],
            'sub_method_id' => [
                'required',
                'uuid',
                Rule::exists('sub_methods', 'id')->where(
                    fn ($query) => $query->whereIn('method_id', Method::crypto()->pluck('id'))
                ),
            ],
            'amount' => ['required', 'numeric', 'decimal:0,5', 'gt:0', 'max:9999999999.99999'],
        ];
    }
}
