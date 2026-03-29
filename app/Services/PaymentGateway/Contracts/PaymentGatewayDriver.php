<?php

namespace App\Services\PaymentGateway\Contracts;

interface PaymentGatewayDriver
{
    /**
     * Initialize a payment request.
     *
     * @param  array{
     *     amount: float,
     *     description: string,
     *     payment_reference: string,
     *     success_url: string,
     *     failure_url: string,
     * }  $payload
     */
    public function initiate(array $payload): array;

    /**
     * Verify a completed payment.
     *
     * @param  string  $transactionId  Gateway transaction ID
     */
    public function verify(string $transactionId): array;

    /**
     * Refund a completed payment.
     *
     * @param  string  $transactionId  Gateway transaction ID
     * @param  float  $amount  Refund amount (null = full refund)
     */
    public function refund(string $transactionId, ?float $amount = null): array;

    /**
     * Check if driver is in sandbox/test mode.
     */
    public function isSandbox(): bool;

    /**
     * Get gateway display name.
     */
    public function getName(): string;

    /**
     * Validate API credentials are configured.
     */
    public function validate(): bool;
}
