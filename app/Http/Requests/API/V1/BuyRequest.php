<?php

namespace App\Http\Requests\API\V1;

use App\Models\Method;
use Illuminate\Validation\Rule;

class BuyRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'asset_id'      => [
                'required',
                'uuid',
                Rule::exists('assets', 'id')->where('active', true),
            ],
            'fiat_amount'   => ['required', 'numeric', 'gt:0'],
            'sub_method_id' => [
                'nullable',
                'uuid',
                Rule::exists('sub_methods', 'id')->where(
                    fn ($query) => $query->whereIn('method_id', Method::fiat()->pluck('id'))
                ),
            ],
        ];
    }
}
