<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: string,
     *     title: string,
     *     start_time: string,
     *     end_time: string,
     *     day_of_week: int,
     *     capacity: int,
     *     enrolled: int,
     *     image_url: string|null,
     *     is_booked: bool
     * }
     */
    public function toArray(Request $request): array
    {
        $startsAt = Carbon::createFromFormat('H:i:s', $this->starts_at);
        $endTime = $startsAt->copy()->addMinutes($this->duration_minutes)->format('H:i');

        return [
            'id' => (string) $this->id,
            'title' => $this->course->name,
            'start_time' => $startsAt->format('H:i'),
            'end_time' => $endTime,
            'day_of_week' => $this->day_of_week,
            'capacity' => $this->capacity,
            'enrolled' => $this->bookings_count ?? 0,
            'image_url' => $this->course->image_url,
            'is_booked' => $this->relationLoaded('bookings')
                ? $this->bookings->isNotEmpty()
                : false,
        ];
    }
}
