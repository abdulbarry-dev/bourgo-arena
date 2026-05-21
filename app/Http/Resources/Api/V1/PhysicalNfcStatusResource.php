<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhysicalNfcStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'has_card' => $this->resource['has_card'],
            'card_uid' => $this->resource['card_uid'],
            'card_status' => $this->resource['card_status'],
            'is_ready' => $this->resource['is_ready'],
            'fallback_methods' => $this->resource['fallback_methods'],
        ];
    }
}
