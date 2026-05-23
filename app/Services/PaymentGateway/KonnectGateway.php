<?php

namespace App\Services\PaymentGateway;

use Illuminate\Support\Facades\Http;

class KonnectGateway
{
    private ?string $apiKey;

    private ?string $apiSecret;

    private bool $sandbox;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('payment.konnect.api_key') ?: null;
        $this->apiSecret = config('payment.konnect.api_secret') ?: null;
        $this->sandbox = config('payment.konnect.sandbox', true);
        $this->baseUrl = $this->sandbox
            ? 'https://api.sandbox.konnect.com.tn'
            : 'https://api.konnect.com.tn';
    }

    public function initiate(array $payload): array
    {
        if (! $this->validate()) {
            throw new \RuntimeException('Konnect API credentials not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
        ])->post($this->baseUrl.'/api/v2/payments/init-payment', [
            'receiverWalletId' => $this->apiSecret,
            'amount' => intval($payload['amount'] * 1000),
            'token' => $payload['payment_reference'],
            'successUrl' => $payload['success_url'],
            'failureUrl' => $payload['failure_url'],
            'description' => $payload['description'],
            'type' => 'immediate',
        ]);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => $response->json('message', 'Payment initiation failed'),
            ];
        }

        $data = $response->json();

        $result = [
            'success' => true,
            'payment_url' => $data['payUrl'] ?? $response->json('payUrl'),
            'payment_reference' => $data['paymentRef'] ?? $response->json('paymentRef'),
            'gateway_transaction_id' => $data['paymentRef'] ?? $response->json('paymentRef'),
        ];

        if (isset($data['requires3DS'])) {
            $result['requires3DS'] = (bool) $data['requires3DS'];
        }

        $result['raw'] = $data;

        return $result;
    }

    public function verify(string $transactionId): array
    {
        if (! $this->validate()) {
            throw new \RuntimeException('Konnect API credentials not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
        ])->get($this->baseUrl.'/api/v2/payments/'.$transactionId);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => $response->json('message', 'Verification failed'),
            ];
        }

        $data = $response->json();

        return [
            'success' => true,
            'status' => $data['status'] ?? null,
            'amount' => isset($data['amount']) ? $data['amount'] / 1000 : null,
            'transaction_id' => $data['paymentRef'] ?? $transactionId,
            'paid_at' => $data['createdAt'] ?? null,
            'raw' => $data,
        ];
    }

    public function refund(string $transactionId, ?float $amount = null): array
    {
        if (! $this->validate()) {
            throw new \RuntimeException('Konnect API credentials not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
        ])->post($this->baseUrl.'/api/v2/payments/'.$transactionId.'/refund', [
            'amount' => $amount ? intval($amount * 1000) : null,
        ]);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => $response->json('message', 'Refund failed'),
            ];
        }

        return [
            'success' => true,
            'refund_id' => $response->json('refundRef'),
            'raw' => $response->json(),
        ];
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    public function getName(): string
    {
        return 'Konnect';
    }

    public function validate(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->apiSecret);
    }
}
