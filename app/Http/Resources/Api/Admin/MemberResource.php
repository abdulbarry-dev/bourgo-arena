<?php

namespace App\Http\Resources\Api\Admin;

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
            'status' => $this->status,
            'loyalty_points' => $this->loyalty_points,
            'is_family_account' => $this->is_family_account,
            'created_at' => $this->created_at->toIso8601String(),
            'subscription' => $this->whenLoaded('activeSubscription', function () {
                return [
                    'id' => $this->activeSubscription->id,
                    'plan_name' => $this->activeSubscription->plan?->name,
                    'status' => $this->activeSubscription->status,
                    'ends_at' => $this->activeSubscription->ends_at?->toDateString(),
                ];
            }),
            'last_check_in' => $this->whenLoaded('checkInEvents', function () {
                $last = $this->checkInEvents->first();

                return $last ? [
                    'id' => $last->id,
                    'at' => $last->checked_in_at->toIso8601String(),
                    'result' => $last->result,
                ] : null;
            }),
        ];
    }
}
