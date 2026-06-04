<?php

declare(strict_types=1);

namespace App\Services\PaymentGateway;

use App\DTOs\Payment\WebhookResultDTO;
use App\Services\PaymentAuditService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FlouciPaymentService
{
    public function __construct(
        protected PaymentAuditService $paymentAuditService
    ) {}

    public function initiatePayment(array $payload): array
    {
        if (! $this->validate()) {
            return $this->fail('Flouci API credentials not configured');
        }

        $requestPayload = $this->buildInitiationPayload($payload);

        try {
            $response = $this->http()->post($this->baseUrl().'/generate_payment', $requestPayload);
        } catch (Throwable $exception) {
            $this->audit('failed', $requestPayload, ['error' => $exception->getMessage()], $payload);

            return $this->fail($exception->getMessage());
        }

        $responsePayload = $response->json();
        $resultPayload = $this->resultPayload($responsePayload);

        if (! $this->isSuccessful($response, $responsePayload, $resultPayload)) {
            $this->audit('failed', $requestPayload, $responsePayload, $payload);

            return $this->fail($this->responseMessage($responsePayload, $resultPayload, 'Payment initiation failed'));
        }

        $paymentId = (string) ($resultPayload['payment_id'] ?? $resultPayload['paymentRef'] ?? $response->json('payment_id') ?? $response->json('paymentRef') ?? $payload['payment_reference'] ?? $payload['order_id'] ?? Str::uuid());
        $redirectUrl = (string) ($resultPayload['link'] ?? $resultPayload['payment_url'] ?? $response->json('link') ?? $response->json('payment_url') ?? '');
        $timeoutSeconds = (int) ($requestPayload['session_timeout_secs'] ?? $payload['expires_in_seconds'] ?? 1200);

        $result = [
            'success' => true,
            'payment_id' => $paymentId,
            'redirect_url' => $redirectUrl,
            'expires_at' => Carbon::now()->addSeconds($timeoutSeconds)->toIso8601String(),
            'payment_url' => $redirectUrl,
            'payment_reference' => $paymentId,
            'gateway_transaction_id' => $paymentId,
            'raw' => $responsePayload,
        ];

        $this->audit('initiated', $requestPayload, $responsePayload, $payload, $paymentId);

        return $result;
    }

    public function verifyPayment(string $transactionId): array
    {
        if (! $this->validate()) {
            return $this->fail('Flouci API credentials not configured');
        }

        $requestPayload = ['payment_id' => $transactionId];

        try {
            $response = $this->http()->get($this->baseUrl().'/verify_payment/'.$transactionId);
        } catch (Throwable $exception) {
            $this->audit('verification_failed', $requestPayload, ['error' => $exception->getMessage()], []);

            return $this->fail($exception->getMessage());
        }

        $responsePayload = $response->json();
        $resultPayload = $this->resultPayload($responsePayload);

        if (! $this->isSuccessful($response, $responsePayload, $resultPayload)) {
            $this->audit('verification_failed', $requestPayload, $responsePayload, []);

            return $this->fail($this->responseMessage($responsePayload, $resultPayload, 'Verification failed'));
        }

        $status = strtolower((string) ($resultPayload['status'] ?? $responsePayload['status'] ?? 'pending'));

        return tap([
            'success' => true,
            'status' => $this->normalizeStatus($status),
            'amount' => isset($resultPayload['amount']) ? ((float) $resultPayload['amount']) / 1000 : (isset($responsePayload['amount']) ? ((float) $responsePayload['amount']) / 1000 : null),
            'transaction_id' => (string) ($resultPayload['payment_id'] ?? $resultPayload['paymentRef'] ?? $transactionId),
            'paid_at' => $resultPayload['createdAt'] ?? $responsePayload['createdAt'] ?? null,
            'raw' => $responsePayload,
        ], function () use ($requestPayload, $responsePayload, $transactionId): void {
            $this->audit('verified', $requestPayload, $responsePayload, [], $transactionId);
        });
    }

    public function validateWebhookSignature(Request $request): bool
    {
        $providedSignature = (string) ($request->header('X-Flouci-Signature') ?? $request->input('signature') ?? '');

        if ($providedSignature === '') {
            return true;
        }

        $payload = array_merge($request->query->all(), $request->json()->all());
        unset($payload['signature']);

        return hash_equals($this->generateSignature($payload), $providedSignature);
    }

    public function normalizeWebhookPayload(Request $request): WebhookResultDTO
    {
        $data = array_merge($request->query->all(), $request->json()->all());

        $status = $this->normalizeStatus(strtolower((string) ($data['status'] ?? 'unknown')));
        $transactionId = $data['payment_id'] ?? $data['paymentRef'] ?? null;
        $orderId = $data['developer_tracking_id'] ?? $data['order_id'] ?? null;

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

    private function http(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->timeout((int) config('payment.webhooks.timeout', 30))
            ->retry([200, 500, 1000], throw: false)
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->credentials(),
            ]);
    }

    private function buildInitiationPayload(array $payload): array
    {
        $requestPayload = array_filter([
            'amount' => $this->toMinorUnits((float) ($payload['amount'] ?? 0)),
            'developer_tracking_id' => $payload['developer_tracking_id'] ?? $payload['payment_reference'] ?? $payload['order_id'] ?? $payload['transaction_reference'] ?? null,
            'accept_card' => (bool) ($payload['accept_card'] ?? true),
            'success_link' => $payload['success_url'] ?? $payload['success_link'] ?? config('app.url'),
            'fail_link' => $payload['failure_url'] ?? $payload['fail_link'] ?? config('app.url'),
            'webhook' => $payload['webhook_url'] ?? $payload['webhook'] ?? null,
            'session_timeout_secs' => (int) ($payload['session_timeout_secs'] ?? $payload['expires_in_seconds'] ?? 1200),
            'client_id' => $payload['client_id'] ?? data_get($payload, 'user.name') ?? $payload['payment_reference'] ?? $payload['order_id'] ?? null,
            'image_url' => $payload['image_url'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);

        $requestPayload['signature'] = $this->generateSignature($requestPayload);

        return $requestPayload;
    }

    private function generateSignature(array $payload): string
    {
        $values = [];

        foreach (Arr::except($payload, ['signature']) as $value) {
            if (is_array($value)) {
                $values[] = implode('', array_map(static fn (mixed $item): string => (string) $item, $value));

                continue;
            }

            $values[] = (string) $value;
        }

        return hash('sha256', implode('', $values).$this->secret());
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
                'payment_gateway' => 'flouci',
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
            Log::warning('Flouci audit logging failed', ['error' => $exception->getMessage()]);
        }
    }

    private function isSuccessful(PendingRequest|Response $response, mixed $responsePayload, array $resultPayload): bool
    {
        if (! $response->successful()) {
            return false;
        }

        if (isset($responsePayload['status']) && strtolower((string) $responsePayload['status']) === 'error') {
            return false;
        }

        if (isset($responsePayload['success']) && ! (bool) $responsePayload['success']) {
            return false;
        }

        if (array_key_exists('success', $resultPayload) && ! (bool) $resultPayload['success']) {
            return false;
        }

        if (isset($resultPayload['status']) && in_array(strtolower((string) $resultPayload['status']), ['error', 'failed'], true)) {
            return false;
        }

        return true;
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'success', 'completed' => 'paid',
            'failure', 'failed' => 'failed',
            default => $status,
        };
    }

    private function resultPayload(mixed $responsePayload): array
    {
        if (! is_array($responsePayload)) {
            return [];
        }

        return is_array($responsePayload['result'] ?? null) ? $responsePayload['result'] : $responsePayload;
    }

    private function responseMessage(mixed $responsePayload, array $resultPayload, string $default): string
    {
        foreach ([Arr::get($resultPayload, 'message'), Arr::get($resultPayload, 'error'), Arr::get(is_array($responsePayload) ? $responsePayload : [], 'message'), Arr::get(is_array($responsePayload) ? $responsePayload : [], 'error')] as $message) {
            if (is_string($message) && $message !== '') {
                return $message;
            }
        }

        return $default;
    }

    private function toMinorUnits(float $amount): int
    {
        return (int) round($amount * 1000);
    }

    private function credentials(): string
    {
        return $this->publicKey().':'.$this->secret();
    }

    private function publicKey(): string
    {
        return (string) (config('payment.providers.flouci.app_token') ?: config('payment.flouci.app_token'));
    }

    private function secret(): string
    {
        return (string) (config('payment.providers.flouci.app_secret') ?: config('payment.flouci.app_secret'));
    }

    private function sandbox(): bool
    {
        return (bool) (config('payment.providers.flouci.sandbox') ?? config('payment.flouci.sandbox', true));
    }

    private function baseUrl(): string
    {
        $configured = config('payment.providers.flouci.base_url') ?: config('payment.flouci.base_url');

        if (is_string($configured) && $configured !== '') {
            return rtrim($configured, '/');
        }

        return $this->sandbox()
            ? 'https://developers.flouci.com/api/v2'
            : 'https://developers.flouci.com/api/v2';
    }

    private function validate(): bool
    {
        return $this->publicKey() !== '' && $this->secret() !== '';
    }

    private function fail(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
        ];
    }
}
