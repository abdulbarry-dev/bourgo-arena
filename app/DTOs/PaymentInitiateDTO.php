<?php

namespace App\DTOs;

readonly class PaymentInitiateDTO
{
    public function __construct(
        public ?int $memberId,
        public ?int $reservationId,
        public ?int $subscriptionId,
        public float|int $amount,
        public ?string $currency,
        public ?string $description,
        public ?string $type,
        public ?string $paymentReference,
        public ?array $metadata,
    ) {}
}
