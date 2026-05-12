<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $startsAt = Carbon::createFromFormat('H:i:s', $this->starts_at);
        $endTime = $startsAt->copy()->addMinutes($this->duration_minutes)->format('H:i');

        return [
            'id' => (string) $this->id,
            'title' => $this->course->name,
            'instructor' => $this->course->instructor,
            'start_time' => $startsAt->format('H:i'),
            'end_time' => $endTime,
            'day_of_week' => $this->day_of_week,
            'category' => $this->course->category ?? $this->course->color,
            'capacity' => $this->capacity,
            'enrolled' => $this->bookings_count ?? 0,
            'icon' => $this->course->icon,
            'image_url' => $this->course->image_url,
        ];
    }
}
