<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'price' => (float) $this->price,
            'duration_days' => $this->duration_days,
            'has_all_courses' => $this->has_all_courses,
            // Include service data, specifically the image_url for UI
            'service' => new ServiceResource($this->whenLoaded('service')),
        ];
    }
}
