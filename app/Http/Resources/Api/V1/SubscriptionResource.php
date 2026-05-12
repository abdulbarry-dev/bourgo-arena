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
            /** @description The unique identifier of the subscription. @example 1 */
            'id' => $this->id,
            /** @description The name of the subscription plan. @example "Monthly Premium" */
            'plan_name' => collect($this->plan)->get('name'),
            /** @description A brief description of what the plan includes. @example "Unlimited access to all courts." */
            'plan_description' => collect($this->plan)->get('description'),
            /** @description The current status of the subscription. @example "active" */
            'status' => $this->status,
            /** @description The start date of the subscription. @format date @example "2024-01-01" */
            'starts_at' => $this->starts_at?->format('Y-m-d'),
            /** @description The expiration date of the subscription. @format date @example "2024-02-01" */
            'ends_at' => $this->ends_at?->format('Y-m-d'),
            /** @description Number of days left until expiration. @example 12 */
            'days_remaining' => $this->daysRemaining(),
            /** @description The method used for payment. @example "credit_card" */
            'payment_method' => $this->payment_method,
            /** @description The total amount paid for this subscription. @example 29.99 */
            'amount_paid' => (float) $this->amount_paid,
        ];
    }
}
