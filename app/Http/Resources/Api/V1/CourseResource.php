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
     * @return array{
     *     id: string,
     *     title: string,
     *     instructor: string|null,
     *     start_time: string,
     *     end_time: string,
     *     day_of_week: int,
     *     category: string,
     *     capacity: int,
     *     enrolled: int,
     *     icon: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        $startsAt = Carbon::createFromFormat('H:i:s', $this->starts_at);
        $endTime = $startsAt->copy()->addMinutes($this->duration_minutes)->format('H:i');

        return [
            /** @description The unique identifier of the scheduled course session. @example "1" */
            'id' => (string) $this->id,
            /** @description The name of the course. @example "Padel Basics" */
            'title' => $this->course->name,
            /** @description The name of the instructor. @example "Sarah Jones" */
            'instructor' => $this->course->instructor,
            /** @description The start time of the session. @example "10:00" */
            'start_time' => $startsAt->format('H:i'),
            /** @description The calculated end time based on duration. @example "11:30" */
            'end_time' => $endTime,
            /** @description The day of the week (1-7). @example 1 */
            'day_of_week' => $this->day_of_week,
            /** @description The category classification of the course. @example "Fitness" */
            'category' => $this->course->category,
            /** @description Maximum number of participants. @example 20 */
            'capacity' => $this->capacity,
            /** @description Number of members currently enrolled. @example 12 */
            'enrolled' => $this->bookings_count ?? 0,
            /** @description The icon identifier for the course. @example "fitness-icon" */
            'icon' => $this->course->icon,
        ];
    }
}
