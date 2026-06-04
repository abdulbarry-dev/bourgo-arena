<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Initiate a payment and return a normalized response.
     *
     * Expected success keys include:
     * - payment_id
     * - redirect_url
     * - expires_at
     */
    public function initiatePayment(Payment $payment, array $options = []): array;

    /**
     * Verify the payment status with the gateway.
     */
    public function verifyPayment(string $transactionId): array;
}
