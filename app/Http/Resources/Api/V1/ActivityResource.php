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
            /** @description The unique identifier of the activity. @example "1" */
            'id' => (string) $this->id,
            /** @description The title of the activity. @example "Padel" */
            'title' => $this->title,
            /** @description Display name of the activity. @example "Padel" */
            'name' => $this->title, // Keep for backward compatibility/tests
            /** @description The category classification. @example "Sports" */
            'category' => $this->category,
            /** @description The minimum starting price. @example 25.0 */
            'base_price' => (float) $this->base_price,
            /** @description The currency for pricing. @example "TND" */
            'currency' => $this->currency,
            /** @description The URL to the featured image. @format uri @example "https://api.bourgo.arena/images/activities/padel.jpg" */
            'image_url' => $this->image_url,
            /** @description The icon identifier. @example "padel-icon" */
            'icon' => $this->icon,
            /** @description Detailed description of the activity. @example "Fast-paced racket sport." */
            'description' => $this->description,
            /** @description Key features or highlights. */
            'features' => $this->features,
            /** @description Average user rating (0-5). @example 4.8 */
            'rating' => (float) $this->rating,
            /** @description Total number of reviews received. @example 120 */
            'review_count' => (int) $this->review_count,
        ];
    }
}
