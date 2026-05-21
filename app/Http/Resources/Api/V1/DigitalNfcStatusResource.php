<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalNfcStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'supported' => $this->resource['supported'],
            'configured' => $this->resource['configured'],
            'eligible' => $this->resource['eligible'],
            'is_ready' => $this->resource['is_ready'],
            'setup_status' => $this->resource['setup_status'],
            'reasons' => $this->resource['reasons'] ?? [],
            'fallback_methods' => $this->resource['fallback_methods'],
        ];
    }
}
