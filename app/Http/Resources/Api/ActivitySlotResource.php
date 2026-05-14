<?php

namespace App\Http\Resources\Api;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class ActivitySlotResource extends BaseJsonResource
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
            'activity_id' => $this->activity_id,
            'date' => $this->date->toDateString(),
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'capacity' => (int) $this->capacity,
            'booked_count' => (int) $this->booked_count,
            'is_available' => (bool) $this->is_available,
            'is_fully_booked' => $this->isFullyBooked(),
        ];
    }
}
