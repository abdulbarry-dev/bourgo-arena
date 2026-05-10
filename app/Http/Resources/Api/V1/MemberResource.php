<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar,
            'birth_date' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender,
            'status' => $this->status,
            'is_parent_account' => $this->is_family_account,
            'subscription_level' => $this->activeSubscription?->plan?->name,
            'subscription_expiry' => $this->activeSubscription?->ends_at?->toDateString(),
            'total_check_ins' => $this->when($this->check_in_events_count !== null, $this->check_in_events_count),
            'children' => MemberResource::collection($this->whenLoaded('children')),
        ];
    }
}
