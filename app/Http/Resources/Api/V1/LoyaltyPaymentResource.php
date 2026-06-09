<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class LoyaltyPaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pointsNeeded = $this->amount ? (int) ceil((float) $this->amount * (int) (config('loyalty.points_to_tnd.rate') ?? 100)) : 0;

        return [
            'id' => $this->id,
            'points_used' => $pointsNeeded,
            'amount_tnd' => (string) $this->amount,
            'type' => $this->type,
            'item_title' => $this->reservation?->activity?->title ?? $this->subscription?->plan?->name ?? null,
            'item_id' => $this->reservation_id ?? $this->subscription_id,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
