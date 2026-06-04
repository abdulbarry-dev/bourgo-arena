<?php

namespace App\Http\Resources\Api\V1;

use App\DTOs\Tier\TierResolution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property TierResolution $resource
 */
class MemberTierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'label' => $this->resource->currentTier->label,
            'multiplier' => (float) $this->resource->currentTier->multiplier,
            'count' => $this->resource->currentSubscriptionCount,
            'next_tier' => $this->resource->nextTier ? [
                'label' => $this->resource->nextTier->label,
                'multiplier' => (float) $this->resource->nextTier->multiplier,
                'required' => $this->resource->nextTier->requiredSubscriptions,
            ] : null,
            'progress_percentage' => (int) $this->resource->progressPercentage,
        ];
    }
}
