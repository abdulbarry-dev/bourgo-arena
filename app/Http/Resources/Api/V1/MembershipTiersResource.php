<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipTiersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'tiers' => TierResource::collection($this->resource['tiers']),
            'family_tiers' => TierResource::collection($this->resource['family_tiers']),
        ];
    }
}
