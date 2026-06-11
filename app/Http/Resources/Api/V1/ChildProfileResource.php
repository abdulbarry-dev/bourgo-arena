<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChildProfileResource extends JsonResource
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
            'id' => (string) $this->id,
            'name' => $this->name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender,
            'avatar_url' => $this->avatar_url,
            'status' => $this->status,
            'is_archived' => (bool) $this->is_archived,
            'has_active_subscription' => $this->whenLoaded('validSubscriptions', function () {
                return $this->validSubscriptions->isNotEmpty();
            }),
            'active_subscription' => $this->whenLoaded('validSubscriptions', function () {
                $subscription = $this->validSubscriptions->first();

                if ($subscription === null) {
                    return null;
                }

                return [
                    'id' => $subscription->id,
                    'plan_id' => $subscription->plan_id,
                    'plan_name' => $subscription->plan?->name,
                    'status' => $subscription->status,
                    'starts_at' => $subscription->starts_at?->toDateString(),
                    'ends_at' => $subscription->ends_at?->toDateString(),
                    'days_remaining' => $subscription->daysRemaining(),
                ];
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
