<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\Payment\WebhookResultDTO;
use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentProviderInterface
{
    /**
     * Get the unique name/slug of the provider (e.g., 'konnect', 'flouci').
     */
    public function getName(): string;

    /**
     * Initiate a payment via the provider's API.
     * Must return an array with at least:
     * - 'success' => bool
     * - 'payment_url' => string|null
     * - 'payment_reference' => string|null
     * - 'gateway_transaction_id' => string|null
     */
    public function initiate(Payment $payment, array $options = []): array;

    /**
     * Verify the payment status with the provider's API.
     * Must return an array with at least:
     * - 'success' => bool
     * - 'status' => string (normalized: 'paid', 'failed', 'pending', etc.)
     * - 'transaction_id' => string|null
     * - 'amount' => float|null
     */
    public function verify(string $transactionId): array;

    /**
     * Process a refund via the provider's API.
     */
    public function refund(string $transactionId, ?float $amount = null): array;

    /**
     * Verify if the incoming webhook request is authentically from the provider.
     */
    public function validateWebhookSignature(Request $request): bool;

    /**
     * Normalize the provider-specific webhook payload into a standardized DTO.
     */
    public function normalizeWebhookPayload(Request $request): WebhookResultDTO;
}
