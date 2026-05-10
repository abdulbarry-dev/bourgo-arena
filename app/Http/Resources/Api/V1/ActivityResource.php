<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
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
            'name' => $this->title,
            'category' => $this->category,
            'base_price' => $this->base_price,
            'currency' => $this->currency,
            'image_url' => $this->image_url,
            'icon' => $this->icon,
            'description' => $this->description,
            'features' => $this->features,
            'rating' => $this->rating,
            'review_count' => $this->review_count,
        ];
    }
}
