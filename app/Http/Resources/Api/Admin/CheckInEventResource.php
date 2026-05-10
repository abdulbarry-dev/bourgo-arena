<?php

namespace App\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckInEventResource extends JsonResource
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
            'member' => [
                'id' => $this->member_id,
                'name' => $this->member?->name ?? __('Unknown'),
            ],
            'card_uid' => $this->card_uid,
            'terminal' => [
                'id' => $this->terminal_id,
                'name' => $this->terminal?->name ?? __('Unknown Terminal'),
            ],
            'result' => $this->result,
            'denial_reason' => $this->denial_reason,
            'is_suspicious' => $this->is_suspicious,
            'checked_in_at' => $this->checked_in_at->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
