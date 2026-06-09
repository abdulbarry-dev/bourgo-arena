<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyPointResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     points: int,
     *     points_amount: int,
     *     is_debit: bool,
     *     transaction_type: string,
     *     source_type: string|null,
     *     source_id: int|null,
     *     idempotency_key: string|null,
     *     created_at: string
     * }
     */
    public function toArray(Request $request): array
    {
        $points = (int) $this->points;

        return [
            'id' => (int) $this->id,
            'points' => $points,
            'points_amount' => abs($points),
            'is_debit' => $points < 0,
            'transaction_type' => (string) $this->transaction_type,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'idempotency_key' => $this->idempotency_key,
            'created_at' => $this->created_at?->toISOString() ?? now()->toISOString(),
        ];
    }
}
