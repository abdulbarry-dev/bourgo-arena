<?php

declare(strict_types=1);

namespace App\Services\Payment\Providers;

use App\Contracts\PaymentProviderInterface;
use App\DTOs\Payment\WebhookResultDTO;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TestingProvider implements PaymentProviderInterface
{
    public function getName(): string
    {
        return 'test';
    }

    public function initiatePayment(Payment $payment, array $options = []): array
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Testing provider is not allowed in production');
        }

        $isFailure = str_contains((string) ($options['description'] ?? ''), 'test_failure');

        if ($isFailure) {
            return [
                'success' => false,
                'error' => 'Simulated test failure',
            ];
        }

        $paymentId = 'test_'.bin2hex(random_bytes(8));
        $successUrl = $options['success_url'] ?? config('app.url');
        $failureUrl = $options['failure_url'] ?? config('app.url');

        $paymentUrl = route('payments.mock-gateway', [
            'amount' => $payment->amount,
            'description' => $options['description'] ?? 'Payment',
            'success_url' => $successUrl,
            'failure_url' => $failureUrl,
            'payment_id' => $paymentId,
        ]);

        return [
            'success' => true,
            'payment_id' => $paymentId,
            'redirect_url' => $paymentUrl,
            'expires_at' => Carbon::now()->addHour()->toIso8601String(),
            'payment_url' => $paymentUrl,
            'payment_reference' => $paymentId,
            'gateway_transaction_id' => $paymentId,
            'raw' => ['testing' => true],
        ];
    }

    public function verifyPayment(string $transactionId): array
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Testing provider is not allowed in production');
        }

        $isFailure = str_contains($transactionId, 'fail');

        return [
            'success' => ! $isFailure,
            'status' => $isFailure ? 'failed' : 'paid',
            'amount' => 0.0,
            'transaction_id' => $transactionId,
            'paid_at' => now()->toIso8601String(),
            'raw' => ['testing' => true],
        ];
    }

    public function validateWebhookSignature(Request $request): bool
    {
        return true;
    }

    public function normalizeWebhookPayload(Request $request): WebhookResultDTO
    {
        return new WebhookResultDTO(
            success: true,
            status: 'paid',
            transactionId: $request->input('transaction_id', 'test_webhook'),
            orderId: $request->input('order_id'),
            paymentReference: $request->input('payment_reference'),
            message: 'Simulated webhook',
            rawPayload: $request->all()
        );
    }
}
