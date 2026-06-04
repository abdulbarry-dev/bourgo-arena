<?php

namespace App\Http\Resources\Api\V1;

use App\DTOs\Tier\TierData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property TierData $resource
 */
class TierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'label' => $this->resource->label,
            'multiplier' => (float) $this->resource->multiplier,
            'requirements' => __($this->resource->requirements),
            'benefits' => __($this->resource->benefits),
        ];
    }
}
