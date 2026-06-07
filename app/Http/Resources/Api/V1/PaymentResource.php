<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'gateway' => $this->gateway,
            'payment_reference' => $this->payment_reference,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
