<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventParticipantResource extends JsonResource
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
            // Cast to string to match EventResource's `id` contract and the
            // mobile client's String `event_id` field.
            'event_id' => (string) $this->event_id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'initials' => $this->user->initials(),
            ],
            'seed_number' => $this->seed_number,
            'status' => $this->status,
            'has_checked_in' => $this->has_checked_in,
            'registered_at' => $this->created_at,
            'event' => new EventResource($this->whenLoaded('event')),
        ];
    }
}
