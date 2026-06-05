<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\Payment\WebhookResultDTO;
use Illuminate\Http\Request;

interface PaymentProviderInterface extends PaymentGatewayInterface
{
    /**
     * Get the unique name/slug of the provider (e.g., 'konnect').
     */
    public function getName(): string;

    /**
     * Verify if the incoming webhook request is authentically from the provider.
     */
    public function validateWebhookSignature(Request $request): bool;

    /**
     * Normalize the provider-specific webhook payload into a standardized DTO.
     */
    public function normalizeWebhookPayload(Request $request): WebhookResultDTO;
}
