<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     first_name: string,
     *     last_name: string,
     *     email: string,
     *     phone: string|null,
     *     avatar_url: string|null,
     *     loyalty_points: int,
     *     birth_date: string|null,
     *     gender: string|null,
     *     status: string,
     *     is_parent_account: bool,
     *     subscription_level: string|null,
     *     subscription_expiry: string|null,
     *     children: MemberResource[]
     * }
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
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,
            'loyalty_points' => $this->loyalty_points ?? 0,
            'birth_date' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender,
            'status' => $this->status,
            'state' => $this->state,
            'is_parent_account' => (bool) $this->is_family_account,

            'children' => MemberResource::collection($this->whenLoaded('children')),
            'valid_subscriptions' => $this->whenLoaded('validSubscriptions', function () {
                return $this->validSubscriptions->map(function ($subscription) {
                    return [
                        'id' => $subscription->id,
                        'plan_id' => $subscription->plan_id,
                        'status' => $subscription->status,
                        'starts_at' => $subscription->starts_at?->toDateString(),
                        'ends_at' => $subscription->ends_at?->toDateString(),
                        'plan_name' => $subscription->plan?->name,
                    ];
                });
            }),
        ];
    }
}
