<?php

declare(strict_types=1);

namespace App\Services\Payment\Providers;

use App\Contracts\PaymentProviderInterface;
use App\DTOs\Payment\WebhookResultDTO;
use App\Models\Payment;
use App\Services\PaymentGateway\KonnectPaymentService;
use Illuminate\Http\Request;

class KonnectProvider implements PaymentProviderInterface
{
    public function __construct(
        protected ?KonnectPaymentService $service = null
    ) {
        $this->service ??= app(KonnectPaymentService::class);
    }

    public function getName(): string
    {
        return 'konnect';
    }

    public function initiatePayment(Payment $payment, array $options = []): array
    {
        return $this->service->initiatePayment($this->buildPayload($payment, $options));
    }

    public function initiate(Payment $payment, array $options = []): array
    {
        return $this->initiatePayment($payment, $options);
    }

    public function verifyPayment(string $transactionId): array
    {
        return $this->service->verifyPayment($transactionId);
    }

    public function verify(string $transactionId): array
    {
        return $this->verifyPayment($transactionId);
    }

    public function validateWebhookSignature(Request $request): bool
    {
        $secret = config('payment.providers.konnect.webhook_secret');

        if (empty($secret)) {
            return false;
        }

        $data = array_merge($request->query->all(), $request->json()->all());
        $payload = json_encode($data);

        $expected = hash_hmac('sha256', $payload, (string) $secret);

        $header = (string) $request->header('X-konnect-Signature', '');

        return hash_equals($expected, $header);
    }

    public function normalizeWebhookPayload(Request $request): WebhookResultDTO
    {
        $data = array_merge($request->query->all(), $request->json()->all());

        $status = strtolower((string) ($data['status'] ?? ''));
        $transactionId = $data['paymentRef'] ?? $data['payment_id'] ?? ($data['transaction_id'] ?? null);
        $orderId = $data['orderId'] ?? $data['order_id'] ?? null;
        $paymentReference = $data['token'] ?? $data['payment_reference'] ?? null;

        return new WebhookResultDTO(
            success: in_array($status, ['paid', 'completed', 'success'], true),
            status: $status ?: 'unknown',
            transactionId: $transactionId,
            orderId: $orderId,
            paymentReference: $paymentReference,
            message: null,
            rawPayload: $data
        );
    }

    private function buildPayload(Payment $payment, array $options): array
    {
        $reservation = $payment->reservation;
        $member = $payment->member;

        return array_filter([
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency ?? 'TND',
            'payment_reference' => $payment->payment_reference,
            'order_id' => $payment->payment_reference,
            'description' => $options['description'] ?? 'Payment',
            'success_url' => $options['success_url'] ?? config('app.url'),
            'failure_url' => $options['failure_url'] ?? config('app.url'),
            'webhook_url' => $options['webhook_url'] ?? rtrim((string) config('app.url'), '/').'/api/v1/payments/webhook/konnect',
            'type' => $options['type'] ?? 'immediate',
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
        ], static fn (mixed $value): bool => $value !== null && $value !== [] && $value !== '');
    }
}
