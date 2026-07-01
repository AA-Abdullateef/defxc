<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepositPhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'  => $this->id,
            'url' => $this->url(),
        ];
    }
}