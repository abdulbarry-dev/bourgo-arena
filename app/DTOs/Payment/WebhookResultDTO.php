<?php

declare(strict_types=1);

namespace App\DTOs\Payment;

class WebhookResultDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $transactionId = null,
        public readonly ?string $orderId = null,
        public readonly ?string $paymentReference = null,
        public readonly ?string $message = null,
        public readonly array $rawPayload = []
    ) {}

    /**
     * Determine if the status represents a successful payment.
     */
    public function isPaid(): bool
    {
        return in_array(strtolower($this->status), ['paid', 'completed', 'success'], true);
    }

    /**
     * Determine if the status represents a refund.
     */
    public function isRefund(): bool
    {
        return in_array(strtolower($this->status), ['refunded', 'refund', 'partially_refunded', 'partial_refund', 'refunded_partially'], true);
    }
}
