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
     *     total_check_ins: int,
     *     children: MemberResource[]
     * }
     */
    public function toArray(Request $request): array
    {
        $nameParts = explode(' ', $this->name, 2);
        $firstName = $nameParts[0] ?? $this->name;
        $lastName = $nameParts[1] ?? '';

        return [
            /** @description The unique identifier of the member. @example 1 */
            'id' => $this->id,
            /** @description The full name of the member. @example "John Doe" */
            'name' => $this->name,
            /** @description The first name of the member. @example "John" */
            'first_name' => $firstName,
            /** @description The last name of the member. @example "Doe" */
            'last_name' => $lastName,
            /** @description The email address of the member. @format email @example "john@example.com" */
            'email' => $this->email,
            /** @description The phone number of the member. @example "+21612345678" */
            'phone' => $this->phone,
            /** @description The URL to the member's avatar image. @format uri @example "https://api.bourgo.arena/storage/avatars/1.png" */
            'avatar_url' => $this->avatar ? asset('storage/'.$this->avatar) : null,
            /** @description The current loyalty points balance. @example 150 */
            'loyalty_points' => $this->loyalty_points ?? 0,
            /** @description The member's birth date. @format date @example "1990-01-01" */
            'birth_date' => $this->date_of_birth?->toDateString(),
            /** @description The gender of the member. @example "male" */
            'gender' => $this->gender,
            /** @description The current status of the member account. @example "active" */
            'status' => $this->status,
            /** @description Whether this is a parent/family account. @example true */
            'is_parent_account' => (bool) $this->is_family_account,
            /** @description The name of the active subscription plan. @example "Premium" */
            'subscription_level' => $this->activeSubscription?->plan?->name,
            /** @description The expiration date of the current subscription. @format date @example "2024-12-31" */
            'subscription_expiry' => $this->activeSubscription?->ends_at?->toDateString(),
            /** @description Total number of check-ins recorded. @example 42 */
            'total_check_ins' => $this->check_in_events_count ?? 0,
            /** @description List of linked child accounts. */
            'children' => MemberResource::collection($this->whenLoaded('children')),
        ];
    }
}
