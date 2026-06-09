<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: string,
     *     session_id: string,
     *     course_id: string,
     *     course_name: string,
     *     date: string,
     *     start_time: string,
     *     end_time: string,
     *     status: string
     * }
     */
    public function toArray(Request $request): array
    {
        $session = $this->courseSession;
        $startsAt = Carbon::createFromFormat('H:i:s', $session->starts_at);
        $endTime = $startsAt->copy()->addMinutes($session->duration_minutes)->format('H:i');

        return [
            'id' => (string) $this->id,
            'session_id' => (string) $this->course_session_id,
            'course_id' => (string) $session->course_id,
            'course_name' => $session->course->name,
            'date' => $this->date?->toDateString(),
            'start_time' => $startsAt->format('H:i'),
            'end_time' => $endTime,
            'status' => $this->status,
        ];
    }
}
