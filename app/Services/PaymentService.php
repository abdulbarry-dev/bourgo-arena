<?php

namespace App\Services;

use App\DTOs\PaymentInitiateDTO;
use App\Events\PaymentPaid;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Services\PaymentGateway\KonnectGateway;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        protected KonnectGateway $gateway,
        protected PaymentRepository $paymentRepository
    ) {}

    public function findByIdentifiers(?string $paymentReference = null, ?string $gatewayTransactionId = null): ?Payment
    {
        return $this->paymentRepository->findByIdentifiers($paymentReference, $gatewayTransactionId);
    }

    public function createPayment(PaymentInitiateDTO $dto): Payment
    {
        $geoService = app(GeoLocationService::class);

        try {
            $geo = $geoService->detect(request());
            $countryCode = $geo->countryCode;
            $ipAddress = $geo->ip;
        } catch (\Throwable $e) {
            $countryCode = null;
            $ipAddress = request()->ip();
        }

        return $this->paymentRepository->createPayment([
            'member_id' => $dto->memberId,
            'reservation_id' => $dto->reservationId,
            'subscription_id' => $dto->subscriptionId,
            'driver' => 'konnect',
            'type' => $dto->type ?? 'reservation',
            'amount' => $dto->amount,
            'status' => 'pending',
            'payment_reference' => $dto->paymentReference ?? 'konnect_'.bin2hex(random_bytes(6)),
            'metadata' => $dto->metadata,
            'ip_address' => $ipAddress,
            'country_code' => $countryCode,
        ]);
    }

    public function initiate(Payment $payment, array $options = []): array
    {
        $payload = [
            'amount' => (float) $payment->amount,
            'description' => $options['description'] ?? 'Payment',
            'payment_reference' => $payment->payment_reference,
            'success_url' => $options['success_url'] ?? config('app.url'),
            'failure_url' => $options['failure_url'] ?? config('app.url'),
        ];

        $result = $this->gateway->initiate($payload);

        if (! empty($result['success'])) {
            $this->paymentRepository->updatePayment($payment, [
                'status' => 'initiated',
                'gateway_transaction_id' => $result['gateway_transaction_id'] ?? null,
                'metadata' => $result,
            ]);
        } else {
            $this->paymentRepository->updatePayment($payment, ['status' => 'failed', 'metadata' => $result]);
        }

        return $result;
    }

    public function verify(Payment $payment, ?string $transactionId = null): array
    {
        $transactionId = $transactionId ?? $payment->gateway_transaction_id ?? $payment->payment_reference;

        $result = $this->gateway->verify($transactionId);

        $status = $result['status'] ?? null;

        if ($status && in_array(strtolower($status), ['paid', 'completed'], true)) {
            $this->paymentRepository->updatePayment($payment, [
                'status' => 'paid',
                'metadata' => $result,
                'verified_at' => now(),
                'gateway_transaction_id' => $result['transaction_id'] ?? $payment->gateway_transaction_id,
            ]);

            PaymentPaid::dispatch($payment->fresh());

            return ['success' => true, 'status' => 'paid', 'data' => $result];
        }

        $this->paymentRepository->updatePayment($payment, ['status' => 'failed', 'metadata' => $result]);

        return ['success' => false, 'status' => $status ?? 'unknown', 'data' => $result];
    }

    public function handleWebhook(array $data): array
    {
        // Normalize identifiers
        $transactionId = $data['paymentRef'] ?? $data['payment_id'] ?? $data['transaction_id'] ?? null;
        $orderId = $data['order_id'] ?? $data['token'] ?? $data['orderId'] ?? $data['payment_reference'] ?? null;

        $payment = null;

        if ($transactionId) {
            $payment = $this->paymentRepository->findByIdentifiers(null, $transactionId);
        }

        if ($payment === null && $orderId) {
            $payment = $this->paymentRepository->findByIdentifiers($orderId, null);
        }

        if ($payment === null && ! empty($data['payment_reference'])) {
            $payment = $this->paymentRepository->findByIdentifiers($data['payment_reference'], null);
        }

        if ($payment === null) {
            Log::info('Payment webhook: no matching payment', ['payload' => $data]);

            return ['success' => false, 'error' => 'payment_not_found'];
        }

        $status = strtolower((string) ($data['status'] ?? $data['transaction_status'] ?? ''));

        if ($payment->status === 'paid' && in_array($status, ['paid', 'completed', 'success'], true)) {
            return ['success' => true, 'message' => 'already_processed'];
        }

        if (in_array($status, ['paid', 'completed', 'success'], true)) {
            $this->paymentRepository->updatePayment($payment, [
                'status' => 'paid',
                'verified_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], $data),
            ]);

            PaymentPaid::dispatch($payment->fresh());

            return ['success' => true];
        }

        if (in_array($status, ['refunded', 'refund', 'partially_refunded', 'partial_refund', 'refunded_partially'], true)) {
            $this->paymentRepository->updatePayment($payment, [
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], $data, ['refund_reason' => 'gateway_refund']),
            ]);

            return ['success' => true, 'status' => 'refunded'];
        }

        $this->paymentRepository->updatePayment($payment, ['status' => 'failed', 'metadata' => $data]);

        return ['success' => false, 'status' => $status];
    }

    /**
     * Mark a payment as failed and attach metadata for diagnostics.
     */
    public function markFailed(Payment $payment, $metadata = null): void
    {
        $this->paymentRepository->updatePayment($payment, ['status' => 'failed', 'metadata' => $metadata]);
    }
}
