<?php

declare(strict_types=1);

namespace App\Services\PaymentGateway;

use App\Services\PaymentAuditService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class KonnectPaymentService
{
    public function __construct(
        protected PaymentAuditService $paymentAuditService
    ) {}

    public function initiatePayment(array $payload): array
    {
        if (! $this->validate()) {
            return $this->fail('Konnect API credentials not configured');
        }

        $requestPayload = $this->buildInitiationPayload($payload);

        Log::debug('Konnect Initiation Request', ['url' => $this->baseUrl().'/payments/init-payment', 'payload' => $requestPayload]);

        try {
            $response = $this->http()->post($this->baseUrl().'/payments/init-payment', $requestPayload);
        } catch (Throwable $exception) {
            $this->audit('failed', $requestPayload, ['error' => $exception->getMessage()], $payload);

            return $this->fail($exception->getMessage());
        }

        $responsePayload = $response->json();

        if (! $response->successful()) {
            Log::error('Konnect Initiation Failed', [
                'status' => $response->status(),
                'payload' => $requestPayload,
                'response' => $responsePayload,
            ]);
            $this->audit('failed', $requestPayload, $responsePayload, $payload);

            return $this->fail($this->responseMessage($responsePayload, 'Payment initiation failed'));
        }

        $paymentId = $responsePayload['paymentRef'] ?? $response->json('paymentRef');
        $redirectUrl = $responsePayload['payUrl'] ?? $response->json('payUrl');
        $expiresAt = Carbon::now()->addMinutes((int) ($payload['expires_in_minutes'] ?? 20));

        $result = [
            'success' => true,
            'payment_id' => $paymentId,
            'redirect_url' => $redirectUrl,
            'expires_at' => $expiresAt->toIso8601String(),
            'payment_url' => $redirectUrl,
            'payment_reference' => $paymentId,
            'gateway_transaction_id' => $paymentId,
            'raw' => $responsePayload,
        ];

        if (isset($responsePayload['requires3DS'])) {
            $result['requires3DS'] = (bool) $responsePayload['requires3DS'];
        }

        $this->audit('initiated', $requestPayload, $responsePayload, $payload, $paymentId);

        return $result;
    }

    public function verifyPayment(string $transactionId): array
    {
        if (! $this->validate()) {
            return $this->fail('Konnect API credentials not configured');
        }

        $requestPayload = ['transaction_id' => $transactionId];

        try {
            $response = $this->http()->get($this->baseUrl().'/payments/'.$transactionId);
        } catch (Throwable $exception) {
            $this->audit('verification_failed', $requestPayload, ['error' => $exception->getMessage()], []);

            return $this->fail($exception->getMessage());
        }

        $responsePayload = $response->json();

        if (! $response->successful()) {
            $this->audit('verification_failed', $requestPayload, $responsePayload, []);

            return $this->fail($this->responseMessage($responsePayload, 'Verification failed'));
        }

        $result = [
            'success' => true,
            'status' => $responsePayload['status'] ?? null,
            'amount' => isset($responsePayload['amount']) ? $responsePayload['amount'] / 1000 : null,
            'transaction_id' => $responsePayload['paymentRef'] ?? $transactionId,
            'paid_at' => $responsePayload['createdAt'] ?? null,
            'raw' => $responsePayload,
        ];

        $this->audit('verified', $requestPayload, $responsePayload, [], $transactionId);

        return $result;
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->timeout((int) config('payment.webhooks.timeout', 30))
            ->retry([200, 500, 1000], throw: false)
            ->withHeaders([
                'x-api-key' => $this->apiKey(),
            ]);
    }

    private function buildInitiationPayload(array $payload): array
    {
        $amount = (float) ($payload['amount'] ?? 0);
        $user = $payload['user'] ?? Arr::only($payload, ['user_name', 'user_email', 'user_phone']);

        $customer = null;
        if (! empty($user)) {
            $name = $user['name'] ?? $user['user_name'] ?? 'Customer User';
            $parts = explode(' ', (string) $name, 2);

            $customer = array_filter([
                'firstname' => trim($parts[0] ?? 'Customer'),
                'lastname' => trim($parts[1] ?? 'User'),
                'email' => $user['email'] ?? $user['user_email'] ?? 'customer@example.com',
                'phoneNumber' => $user['phone'] ?? $user['user_phone'] ?? null,
            ]);
        }

        $webhookUrl = $payload['webhook_url'] ?? rtrim((string) config('app.url'), '/').'/api/v1/payments/webhook/konnect';
        if ($this->sandbox() && str_contains($webhookUrl, 'localhost')) {
            $webhookUrl = null;
        }

        return array_filter([
            'receiverWalletId' => $this->apiSecret(),
            'amount' => intval($amount * 1000),
            'currency' => 'TND',
            'orderId' => (string) ($payload['order_id'] ?? $payload['payment_reference'] ?? $payload['transaction_reference'] ?? Str::random(12)),
            'successUrl' => $payload['success_url'] ?? config('app.url'),
            'failureUrl' => $payload['failure_url'] ?? config('app.url'),
            'webhookUrl' => $webhookUrl,
            'description' => $payload['description'] ?? 'Payment',
            'type' => $payload['type'] ?? 'immediate',
            'customer' => $customer,
        ], static fn (mixed $value): bool => $value !== null && $value !== [] && $value !== '');
    }

    private function audit(
        string $status,
        array $requestPayload,
        mixed $responsePayload,
        array $context,
        ?string $transactionId = null
    ): void {
        try {
            $this->paymentAuditService->logStandalone([
                'transaction_id' => $transactionId ?? $context['payment_reference'] ?? $context['order_id'] ?? (string) Str::uuid(),
                'user_id' => $context['user_id'] ?? null,
                'reservation_id' => $context['reservation_id'] ?? null,
                'amount' => $context['amount'] ?? ($requestPayload['amount'] ?? 0) / 1000,
                'currency' => $context['currency'] ?? $requestPayload['currency'] ?? 'TND',
                'payment_gateway' => 'konnect',
                'transaction_status' => $status,
                'external_gateway_reference' => $context['external_gateway_reference'] ?? $transactionId,
                'reservation_details' => $context['reservation_details'] ?? null,
                'user_information' => $context['user_information'] ?? $context['user'] ?? null,
                'ip_address' => $context['ip_address'] ?? null,
                'user_agent' => $context['user_agent'] ?? null,
                'request_payload' => $requestPayload,
                'response_payload' => $responsePayload,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Konnect audit logging failed', ['error' => $exception->getMessage()]);
        }
    }

    private function validate(): bool
    {
        return ! empty($this->apiKey()) && ! empty($this->apiSecret());
    }

    private function apiKey(): ?string
    {
        return config('payment.providers.konnect.api_key') ?: config('payment.konnect.api_key');
    }

    private function apiSecret(): ?string
    {
        return config('payment.providers.konnect.api_secret') ?: config('payment.konnect.api_secret');
    }

    private function sandbox(): bool
    {
        return (bool) (config('payment.providers.konnect.sandbox') ?? config('payment.konnect.sandbox', true));
    }

    private function baseUrl(): string
    {
        $configured = config('payment.providers.konnect.base_url') ?: config('payment.konnect.base_url');

        if (is_string($configured) && $configured !== '') {
            return rtrim($configured, '/');
        }

        return $this->sandbox()
            ? 'https://api.sandbox.konnect.network/api/v2'
            : 'https://api.konnect.network/api/v2';
    }

    private function responseMessage(mixed $responsePayload, string $default): string
    {
        if (is_array($responsePayload)) {
            return (string) ($responsePayload['message'] ?? $responsePayload['error'] ?? $default);
        }

        return $default;
    }

    private function fail(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
        ];
    }
}
