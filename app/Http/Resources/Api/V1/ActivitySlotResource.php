<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivitySlotResource extends JsonResource
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
            'time' => Carbon::createFromFormat('H:i:s', $this->starts_at)->format('H:i'),
            'available' => $this->is_available,
            'start_time' => $this->starts_at,
            'end_time' => $this->ends_at,
            'capacity' => $this->capacity,
            'booked_count' => $this->booked_count,
            'is_available' => $this->is_available,
            'is_fully_booked' => $this->isFullyBooked(),
        ];
    }
}
