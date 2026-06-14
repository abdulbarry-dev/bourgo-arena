<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivitySessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->starts_at,
            'end_time' => Carbon::parse($this->starts_at)->addMinutes($this->duration_minutes)->format('H:i:s'),
            'duration_minutes' => $this->duration_minutes,
            'capacity' => $this->capacity,
            'status' => $this->getStatus(Carbon::parse($this->starts_at_date ?? now())),
        ];
    }
}
