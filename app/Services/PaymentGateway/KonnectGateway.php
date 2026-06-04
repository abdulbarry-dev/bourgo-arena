<?php

namespace App\Services\PaymentGateway;

class KonnectGateway
{
    public function __construct(
        protected ?KonnectPaymentService $service = null
    ) {
        $this->service ??= app(KonnectPaymentService::class);
    }

    public function initiate(array $payload): array
    {
        $result = $this->service->initiatePayment($payload);

        if (($result['success'] ?? false) === false && ($result['error'] ?? null) === 'Konnect API credentials not configured') {
            throw new \RuntimeException('Konnect API credentials not configured');
        }

        return $result;
    }

    public function verify(string $transactionId): array
    {
        $result = $this->service->verifyPayment($transactionId);

        if (($result['success'] ?? false) === false && ($result['error'] ?? null) === 'Konnect API credentials not configured') {
            throw new \RuntimeException('Konnect API credentials not configured');
        }

        return $result;
    }

    public function isSandbox(): bool
    {
        return true;
    }

    public function getName(): string
    {
        return 'Konnect';
    }

    public function validate(): bool
    {
        return true;
    }
}
