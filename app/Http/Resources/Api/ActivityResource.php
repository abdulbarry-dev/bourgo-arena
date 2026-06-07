<?php

namespace App\Http\Resources\Api;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class ActivityResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'base_price' => (float) $this->base_price,
            'image_url' => $this->image_url,
            'description' => $this->description,
            'features' => $this->features,
            'rating' => (float) $this->rating,
            'review_count' => (int) $this->review_count,
            'is_active' => (bool) $this->is_active,
            'slots' => ActivitySlotResource::collection($this->whenLoaded('slots')),
        ];
    }
}
