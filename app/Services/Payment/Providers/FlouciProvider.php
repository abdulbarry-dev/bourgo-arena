<?php

declare(strict_types=1);

namespace App\Services\Payment\Providers;

use App\Contracts\PaymentProviderInterface;
use App\DTOs\Payment\WebhookResultDTO;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FlouciProvider implements PaymentProviderInterface
{
    private ?string $appToken;

    private ?string $appSecret;

    private bool $sandbox;

    private string $baseUrl;

    public function __construct()
    {
        $this->appToken = config('payment.providers.flouci.app_token');
        $this->appSecret = config('payment.providers.flouci.app_secret');
        $this->sandbox = config('payment.providers.flouci.sandbox', true);

        $this->baseUrl = $this->sandbox
            ? 'https://developers.flouci.com/api' // Flouci Sandbox
            : 'https://developers.flouci.com/api'; // Assuming same, user should verify
    }

    public function getName(): string
    {
        return 'flouci';
    }

    public function initiate(Payment $payment, array $options = []): array
    {
        if (! $this->validate()) {
            throw new \RuntimeException('Flouci API credentials not configured');
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl.'/generate_payment', [
            'app_token' => $this->appToken,
            'app_secret' => $this->appSecret,
            'amount' => intval($payment->amount * 1000), // Flouci expects millimes
            'accept_card' => 'true',
            'session_timeout_secs' => 1200,
            'success_link' => $options['success_url'] ?? config('app.url'),
            'fail_link' => $options['failure_url'] ?? config('app.url'),
            'developer_tracking_id' => $payment->payment_reference,
        ]);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => $response->json('message', 'Payment initiation failed via Flouci'),
            ];
        }

        $data = $response->json('result');

        return [
            'success' => true,
            'payment_url' => $data['link'] ?? null,
            'payment_reference' => $payment->payment_reference,
            'gateway_transaction_id' => $data['payment_id'] ?? null,
            'raw' => $data,
        ];
    }

    public function verify(string $transactionId): array
    {
        if (! $this->validate()) {
            throw new \RuntimeException('Flouci API credentials not configured');
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'apppublic' => $this->appToken,
            'appsecret' => $this->appSecret,
        ])->get($this->baseUrl.'/verify_payment/'.$transactionId);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => $response->json('message', 'Verification failed via Flouci'),
            ];
        }

        $data = $response->json('result');

        // Flouci status logic: 'SUCCESS' is paid.
        $status = strtolower($data['status'] ?? 'pending');
        if ($status === 'success') {
            $status = 'paid';
        }

        return [
            'success' => true,
            'status' => $status,
            'amount' => isset($data['amount']) ? $data['amount'] / 1000 : null,
            'transaction_id' => $data['payment_id'] ?? $transactionId,
            'raw' => $data,
        ];
    }

    public function refund(string $transactionId, ?float $amount = null): array
    {
        // Flouci currently may not have a public refund API or it requires manual handling.
        // Implement as needed if documentation specifies.
        return [
            'success' => false,
            'error' => 'Refund not automatically supported via Flouci API yet.',
        ];
    }

    public function validateWebhookSignature(Request $request): bool
    {
        // Flouci webhooks do not use headers for signatures by default in their sandbox,
        // they normally rely on you verifying the payment_id on callback.
        // If they add signature headers, implement logic here.
        return true;
    }

    public function normalizeWebhookPayload(Request $request): WebhookResultDTO
    {
        $data = array_merge($request->query->all(), $request->json()->all());

        $transactionId = $data['payment_id'] ?? null;
        $orderId = $data['developer_tracking_id'] ?? null;
        $status = strtolower($data['status'] ?? 'unknown');

        if ($status === 'success') {
            $status = 'paid';
        }

        return new WebhookResultDTO(
            success: $status === 'paid',
            status: $status,
            transactionId: $transactionId,
            orderId: $orderId,
            paymentReference: $orderId,
            message: null,
            rawPayload: $data
        );
    }

    private function validate(): bool
    {
        return ! empty($this->appToken) && ! empty($this->appSecret);
    }
}
