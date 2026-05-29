<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'sport_type' => $this->sport_type,
            'format' => $this->format,
            'max_participants' => $this->max_participants,
            'participants_count' => $this->whenCounted('participants'),
            'registration_deadline' => $this->registration_deadline,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'requires_check_in' => $this->requires_check_in,
            'status' => $this->status,
        ];
    }
}
