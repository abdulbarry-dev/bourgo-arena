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
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'gateway' => $this->gateway,
            'payment_method' => $this->normalizeDriver($this->driver),
            'payment_reference' => $this->payment_reference,
            'reservation_id' => $this->reservation_id,
            'subscription_id' => $this->subscription_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function normalizeDriver(?string $driver): ?string
    {
        return match ($driver) {
            'konnect' => 'konnect',
            'cash' => 'cash',
            default => null,
        };
    }
}
