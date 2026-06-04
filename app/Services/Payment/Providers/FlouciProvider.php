<?php

declare(strict_types=1);

namespace App\Services\Payment\Providers;

use App\Contracts\PaymentProviderInterface;
use App\DTOs\Payment\WebhookResultDTO;
use App\Models\Payment;
use App\Services\PaymentGateway\FlouciPaymentService;
use Illuminate\Http\Request;

class FlouciProvider implements PaymentProviderInterface
{
    public function __construct(
        protected ?FlouciPaymentService $service = null
    ) {
        $this->service ??= app(FlouciPaymentService::class);
    }

    public function getName(): string
    {
        return 'flouci';
    }

    public function initiatePayment(Payment $payment, array $options = []): array
    {
        $result = $this->service->initiatePayment($this->buildPayload($payment, $options));

        if (($result['success'] ?? false) === false && ($result['error'] ?? null) === 'Flouci API credentials not configured') {
            throw new \RuntimeException('Flouci API credentials not configured');
        }

        return $result;
    }

    public function initiate(Payment $payment, array $options = []): array
    {
        return $this->initiatePayment($payment, $options);
    }

    public function verifyPayment(string $transactionId): array
    {
        $result = $this->service->verifyPayment($transactionId);

        if (($result['success'] ?? false) === false && ($result['error'] ?? null) === 'Flouci API credentials not configured') {
            throw new \RuntimeException('Flouci API credentials not configured');
        }

        return $result;
    }

    public function verify(string $transactionId): array
    {
        return $this->verifyPayment($transactionId);
    }

    public function validateWebhookSignature(Request $request): bool
    {
        return $this->service->validateWebhookSignature($request);
    }

    public function normalizeWebhookPayload(Request $request): WebhookResultDTO
    {
        return $this->service->normalizeWebhookPayload($request);
    }

    private function buildPayload(Payment $payment, array $options): array
    {
        $reservation = $payment->reservation;
        $member = $payment->member;

        return array_filter([
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency ?? 'TND',
            'payment_reference' => $payment->payment_reference,
            'developer_tracking_id' => $payment->payment_reference,
            'order_id' => $payment->payment_reference,
            'description' => $options['description'] ?? 'Payment',
            'success_url' => $options['success_url'] ?? config('app.url'),
            'failure_url' => $options['failure_url'] ?? config('app.url'),
            'webhook_url' => $options['webhook_url'] ?? null,
            'type' => $options['type'] ?? 'immediate',
            'accept_card' => $options['accept_card'] ?? true,
            'session_timeout_secs' => $options['expires_in_seconds'] ?? 1200,
            'user_id' => $payment->member_id,
            'user' => array_filter([
                'id' => $member?->id,
                'name' => $member?->name,
                'email' => $member?->email,
                'phone' => $member?->phone,
            ], static fn (mixed $value): bool => $value !== null && $value !== ''),
            'reservation_id' => $payment->reservation_id,
            'reservation_details' => $reservation ? [
                'reservation_id' => $reservation->id,
                'activity_id' => $reservation->activity_id,
                'date' => $reservation->date,
                'starts_at' => $reservation->starts_at,
                'ends_at' => $reservation->ends_at,
            ] : null,
            'user_information' => $member ? [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
            ] : null,
            'client_id' => $options['client_id'] ?? $member?->name ?? $payment->payment_reference,
            'image_url' => $options['image_url'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== [] && $value !== '');
    }
}
