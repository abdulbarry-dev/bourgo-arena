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
        $endTime = $startsAt->copy()->addMinutes($this->duration_minutes)->toTimeString();

        return [
            'id' => $this->id,
            'name' => $this->course->name,
            'instructor' => $this->course->instructor,
            'start_time' => $this->starts_at,
            'end_time' => $endTime,
            'day_of_week' => $this->day_of_week,
            'category' => $this->course->category ?? $this->course->color, // Fallback to color if category missing
            'capacity' => $this->capacity,
            'enrolled' => $this->bookings_count ?? 0,
            'icon' => $this->course->icon ?? null,
            'image_url' => $this->course->image_url,
        ];
    }
}
