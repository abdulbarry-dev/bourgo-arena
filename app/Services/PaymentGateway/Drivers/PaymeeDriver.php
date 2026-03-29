<?php

namespace App\Services\PaymentGateway\Drivers;

use App\Services\PaymentGateway\Contracts\PaymentGatewayDriver;
use Illuminate\Support\Facades\Http;

class PaymeeDriver implements PaymentGatewayDriver
{
    private ?string $apiKey;

    private ?string $apiSecret;

    private bool $sandbox;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('payment.paymee.api_key') ?: null;
        $this->apiSecret = config('payment.paymee.api_secret') ?: null;
        $this->sandbox = config('payment.paymee.sandbox', true);
        $this->baseUrl = $this->sandbox
            ? 'https://api-sandbox.paymee.tn'
            : 'https://api.paymee.tn';
    }

    public function initiate(array $payload): array
    {
        if (! $this->validate()) {
            throw new \RuntimeException('Paymee API credentials not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl.'/api/v1/payment/create', [
            'amount' => floatval($payload['amount']),
            'currency' => 'TND',
            'description' => $payload['description'],
            'order_id' => $payload['payment_reference'],
            'return_url' => $payload['success_url'],
            'cancel_url' => $payload['failure_url'],
            'webhook_url' => config('payment.paymee.webhook_url', url('/webhooks/paymee')),
        ]);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => $response->json('error_message', 'Payment initiation failed'),
            ];
        }

        return [
            'success' => true,
            'payment_url' => $response->json('checkout_url'),
            'payment_reference' => $response->json('payment_id'),
            'gateway_transaction_id' => $response->json('payment_id'),
        ];
    }

    public function verify(string $transactionId): array
    {
        if (! $this->validate()) {
            throw new \RuntimeException('Paymee API credentials not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
        ])->get($this->baseUrl.'/api/v1/payment/'.$transactionId);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => $response->json('error_message', 'Verification failed'),
            ];
        }

        $data = $response->json();

        return [
            'success' => true,
            'status' => $data['status'], // paid, pending, failed
            'amount' => $data['amount'],
            'transaction_id' => $data['payment_id'],
            'paid_at' => $data['created_at'],
        ];
    }

    public function refund(string $transactionId, ?float $amount = null): array
    {
        if (! $this->validate()) {
            throw new \RuntimeException('Paymee API credentials not configured');
        }

        $payload = [
            'payment_id' => $transactionId,
        ];

        if ($amount !== null) {
            $payload['amount'] = floatval($amount);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
        ])->post($this->baseUrl.'/api/v1/payment/'.$transactionId.'/refund', $payload);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => $response->json('error_message', 'Refund failed'),
            ];
        }

        return [
            'success' => true,
            'refund_id' => $response->json('refund_id'),
        ];
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    public function getName(): string
    {
        return 'Paymee';
    }

    public function validate(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->apiSecret);
    }
}
