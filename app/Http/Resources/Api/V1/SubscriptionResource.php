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
     *     plan_name: string,
     *     plan_description: string|null,
     *     status: string,
     *     starts_at: string|null,
     *     ends_at: string|null,
     *     days_remaining: int,
     *     payment_method: string|null,
     *     amount_paid: float
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan_name' => collect($this->plan)->get('name'),
            'plan_description' => collect($this->plan)->get('description'),
            'status' => $this->status,
            'starts_at' => $this->starts_at?->format('Y-m-d'),
            'ends_at' => $this->ends_at?->format('Y-m-d'),
            'days_remaining' => $this->daysRemaining(),
            'payment_method' => $this->payment_method,
            'amount_paid' => (float) $this->amount_paid,
        ];
    }
}
