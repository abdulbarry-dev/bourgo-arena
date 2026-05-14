<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: string,
     *     title: string,
     *     name: string,
     *     category: string,
     *     base_price: float,
     *     currency: string,
     *     image_url: string|null,
     *     icon: string|null,
     *     description: string|null,
     *     features: string[]|null,
     *     rating: float,
     *     review_count: int
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'name' => $this->title, // Keep for backward compatibility/tests
            'category' => $this->category,
            'base_price' => (float) $this->base_price,
            'currency' => $this->currency,
            'image_url' => $this->image_url,
            'icon' => $this->icon,
            'description' => $this->description,
            'features' => $this->features,
            'rating' => (float) $this->rating,
            'review_count' => (int) $this->review_count,
        ];
    }
}
