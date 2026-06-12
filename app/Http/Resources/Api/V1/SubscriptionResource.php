<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     member_id: int,
     *     plan: array{
     *         id: int,
     *         name: string,
     *         description: string|null,
     *         price: float,
     *         has_all_courses: bool
     *     },
     *     service: array{
     *         id: int|null,
     *         name: string|null,
     *         slug: string|null,
     *         image_url: string|null
     *     }|null,
     *     status: string,
     *     starts_at: string|null,
     *     ends_at: string|null,
     *     days_remaining: int,
     *     payment_method: string|null,
     *     amount_paid: float,
     *     is_active: bool
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->member_id,
            'plan' => [
                'id' => $this->plan_id,
                'name' => $this->plan?->name,
                'description' => $this->plan?->description,
                'price' => (float) $this->plan?->price,
                'has_all_courses' => (bool) $this->plan?->has_all_courses,
            ],
            'service' => [
                'id' => $this->plan?->service?->id,
                'name' => $this->plan?->service?->name,
                'slug' => $this->plan?->service?->slug,
                'image_url' => $this->plan?->service?->image_url,
            ],
            'status' => $this->status,
            'starts_at' => $this->starts_at?->format('Y-m-d'),
            'ends_at' => $this->ends_at?->format('Y-m-d'),
            'days_remaining' => $this->daysRemaining(),
            'payment_method' => $this->payment_method,
            'amount_paid' => (float) $this->amount_paid,
            'is_active' => $this->isActive(),
        ];
    }
}
