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
        $nameParts = explode(' ', $this->name, 2);
        $firstName = $nameParts[0] ?? $this->name;
        $lastName = $nameParts[1] ?? '';

        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar ? asset('storage/'.$this->avatar) : null,
            'loyalty_points' => $this->loyalty_points ?? 0,
            'birth_date' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender,
            'status' => $this->status,
            'is_parent_account' => (bool) $this->is_family_account,
            'subscription_level' => $this->activeSubscription?->plan?->name,
            'subscription_expiry' => $this->activeSubscription?->ends_at?->toDateString(),
            'total_check_ins' => $this->check_in_events_count ?? 0,
            'children' => MemberResource::collection($this->whenLoaded('children')),
        ];
    }
}
