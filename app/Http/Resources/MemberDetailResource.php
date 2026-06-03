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
            'avatar_url' => $this->avatar_url,
            'status' => $this->status,
            'state' => $this->state,
            'rgpd_consented_at' => $this->rgpd_consented_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'valid_subscriptions' => $this->whenLoaded('validSubscriptions', function () {
                return $this->validSubscriptions->map(function ($subscription) {
                    return [
                        'id' => $subscription->id,
                        'plan_id' => $subscription->plan_id,
                        'status' => $subscription->status,
                        'starts_at' => $subscription->starts_at?->format('Y-m-d'),
                        'ends_at' => $subscription->ends_at?->format('Y-m-d'),
                        'plan_name' => $subscription->plan?->name, // Assuming plan is loaded
                    ];
                });
            }),
        ];
    }
}
