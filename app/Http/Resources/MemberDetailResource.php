<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberDetailResource extends JsonResource
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
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'emergency_contact' => $this->emergency_contact,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'state' => $this->state,
            'rgpd_consented_at' => $this->rgpd_consented_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'active_subscription' => $this->whenLoaded('activeSubscription', function () {
                return $this->activeSubscription ? [
                    'id' => $this->activeSubscription->id,
                    'plan_id' => $this->activeSubscription->plan_id,
                    'status' => $this->activeSubscription->status,
                    'starts_at' => $this->activeSubscription->starts_at?->format('Y-m-d'),
                    'ends_at' => $this->activeSubscription->ends_at?->format('Y-m-d'),
                ] : null;
            }),
        ];
    }
}
