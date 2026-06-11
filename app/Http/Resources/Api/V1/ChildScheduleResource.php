<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChildScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'birth_date' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender,
            'schedule' => $this->when($this->relationLoaded('scheduleItems'), function () {
                return collect($this->scheduleItems)
                    ->groupBy('date')
                    ->map(function ($items, $date) {
                        return [
                            'date' => $date,
                            'items' => $items->toArray(),
                        ];
                    })
                    ->values()
                    ->toArray();
            }),
        ];
    }
}
