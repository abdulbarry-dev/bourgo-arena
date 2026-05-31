<?php

namespace App\DTOs;

readonly class PaymentVerifyDTO
{
    public function __construct(
        public ?string $paymentReference,
        public ?string $gatewayTransactionId,
    ) {}
}
