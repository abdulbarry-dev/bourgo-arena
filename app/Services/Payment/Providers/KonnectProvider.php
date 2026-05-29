<?php

declare(strict_types=1);

namespace App\Services\Payment\Providers;

use App\Contracts\PaymentProviderInterface;
use App\DTOs\Payment\WebhookResultDTO;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KonnectProvider implements PaymentProviderInterface
{
    private ?string $apiKey;

    private ?string $apiSecret;

    private bool $sandbox;

    private string $baseUrl;

    private ?string $webhookSecret;

    public function __construct()
    {
        $this->apiKey = config('payment.providers.konnect.api_key') ?: config('payment.konnect.api_key');
        $this->apiSecret = config('payment.providers.konnect.api_secret') ?: config('payment.konnect.api_secret');
        $this->sandbox = config('payment.providers.konnect.sandbox') ?? config('payment.konnect.sandbox', true);
        $this->webhookSecret = config('payment.providers.konnect.webhook_secret') ?: config('payment.konnect.webhook_secret');

        $this->baseUrl = $this->sandbox
            ? 'https://api.sandbox.konnect.com.tn'
            : 'https://api.konnect.com.tn';
    }

    public function getName(): string
    {
        return 'konnect';
    }

    public function initiate(Payment $payment, array $options = []): array
    {
        if (! $this->validate()) {
            throw new \RuntimeException('Konnect API credentials not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
        ])->post($this->baseUrl.'/api/v2/payments/init-payment', [
            'receiverWalletId' => $this->apiSecret,
            'amount' => intval($payment->amount * 1000),
            'token' => $payment->payment_reference,
            'successUrl' => $options['success_url'] ?? config('app.url'),
            'failureUrl' => $options['failure_url'] ?? config('app.url'),
            'description' => $options['description'] ?? 'Payment',
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

    public function validateWebhookSignature(Request $request): bool
    {
        if (empty($this->webhookSecret)) {
            Log::warning('Webhook secret not configured for Konnect; signature verification bypassed.');

            return true; // Bypass if secret is missing (fallback to verify reference)
        }

        $headerNames = ['X-Konnect-Signature', 'X-konnect-Signature', 'X-Signature', 'X-Signature-256'];
        $signature = null;

        foreach ($headerNames as $h) {
            $val = $request->header($h);
            if (! empty($val)) {
                $signature = $val;
                break;
            }
        }

        if (empty($signature)) {
            Log::warning('Missing Konnect webhook signature header');

            return false;
        }

        $payloadRaw = $request->getContent();
        $expected = hash_hmac('sha256', $payloadRaw, $this->webhookSecret);

        if (! hash_equals($expected, $signature)) {
            Log::warning('Invalid Konnect webhook signature');

            return false;
        }

        return true;
    }

    public function normalizeWebhookPayload(Request $request): WebhookResultDTO
    {
        $data = array_merge($request->query->all(), $request->json()->all());

        $status = strtolower((string) ($data['status'] ?? ''));
        $transactionId = $data['paymentRef'] ?? $data['payment_id'] ?? ($data['transaction_id'] ?? null);
        $orderId = $data['orderId'] ?? $data['order_id'] ?? null;
        $paymentReference = $data['token'] ?? $data['payment_reference'] ?? null;

        return new WebhookResultDTO(
            success: in_array($status, ['paid', 'completed', 'success', 'refunded', 'refund'], true),
            status: $status ?: 'unknown',
            transactionId: $transactionId,
            orderId: $orderId,
            paymentReference: $paymentReference,
            message: null,
            rawPayload: $data
        );
    }

    private function validate(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->apiSecret);
    }
}
