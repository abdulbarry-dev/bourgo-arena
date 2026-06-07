<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventMatchResource extends JsonResource
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
            'round' => $this->round,
            'match_number' => $this->match_number,
            'score' => $this->score,
            'status' => $this->status,
            'participant1' => $this->participant1 ? new EventParticipantResource($this->participant1) : null,
            'participant2' => $this->participant2 ? new EventParticipantResource($this->participant2) : null,
            'winner_id' => $this->winner_id,
            'next_match_id' => $this->next_match_id,
        ];
    }
}
