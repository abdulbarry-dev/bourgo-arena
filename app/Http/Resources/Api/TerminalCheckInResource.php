<?php

namespace App\Http\Resources\Api;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class TerminalCheckInResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'event_id' => $this->id,
            'member_id' => $this->member_id,
            'card_uid' => $this->card_uid,
            'terminal_id' => $this->terminal_id,
            'result' => $this->result,
            'is_suspicious' => (bool) $this->is_suspicious,
            'checked_in_at' => $this->checked_in_at,
        ];
    }
}
