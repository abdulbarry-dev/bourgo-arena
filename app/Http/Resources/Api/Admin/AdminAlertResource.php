<?php

namespace App\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminAlertResource extends JsonResource
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
            'terminal' => [
                'id' => $this->terminal_id,
                'name' => $this->terminal?->name ?? __('Unknown Terminal'),
            ],
            'member' => [
                'id' => $this->member_id,
                'name' => $this->member?->name ?? __('Unknown'),
            ],
            'alert_type' => $this->alert_type,
            'description' => $this->description,
            'count' => $this->count,
            'is_dismissed' => $this->is_dismissed,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
