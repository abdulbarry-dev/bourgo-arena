<?php

namespace App\Services;

use App\DTOs\Payment\WebhookResultDTO;
use App\DTOs\PaymentInitiateDTO;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Services\Payment\PaymentManager;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        protected PaymentManager $paymentManager,
        protected PaymentRepository $paymentRepository,
        protected PaymentAuditService $paymentAuditService
    ) {}

    public function findByIdentifiers(?string $paymentReference = null, ?string $gatewayTransactionId = null): ?Payment
    {
        return $this->paymentRepository->findByIdentifiers($paymentReference, $gatewayTransactionId);
    }

    public function createPayment(PaymentInitiateDTO $dto): Payment
    {
        return $this->paymentRepository->createPayment([
            'member_id' => $dto->memberId,
            'reservation_id' => $dto->reservationId,
            'subscription_id' => $dto->subscriptionId,
            'driver' => $dto->provider ?? $this->paymentManager->getDefaultDriver(),
            'type' => $dto->type ?? 'reservation',
            'amount' => $dto->amount,
            'currency' => $dto->currency ?? 'TND',
            'status' => 'pending',
            'payment_reference' => $dto->paymentReference ?? 'konnect_'.bin2hex(random_bytes(6)),
            'metadata' => $dto->metadata,
        ]);
    }

    public function initiate(Payment $payment, array $options = []): array
    {
        $provider = $this->paymentManager->driver($payment->driver);

        $result = $provider->initiatePayment($payment, $options);

        if (! empty($result['success'])) {
            $this->paymentRepository->updatePayment($payment, [
                'status' => 'initiated',
                'gateway_transaction_id' => $result['gateway_transaction_id'] ?? null,
                'metadata' => $result['raw'] ?? $result,
            ]);
        } else {
            $this->paymentRepository->updatePayment($payment, ['status' => 'failed', 'metadata' => $result]);
        }

        $this->auditPayment($payment, [
            'transaction_status' => ! empty($result['success']) ? 'initiated' : 'failed',
            'request_payload' => $options,
            'response_payload' => $result,
            'external_gateway_reference' => $result['gateway_transaction_id'] ?? $payment->gateway_transaction_id,
            'payment_gateway' => $payment->driver,
        ]);

        return $result;
    }

    public function verify(Payment $payment, ?string $transactionId = null): array
    {
        $provider = $this->paymentManager->driver($payment->driver);

        $transactionId = $transactionId ?? $payment->gateway_transaction_id ?? $payment->payment_reference;

        $result = $provider->verifyPayment($transactionId);

        $status = $result['status'] ?? null;

        if (! empty($result['success']) && in_array(strtolower((string) $status), ['paid', 'completed'], true)) {
            $this->paymentRepository->updatePayment($payment, [
                'status' => 'paid',
                'metadata' => $result['raw'] ?? $result,
                'verified_at' => now(),
                'gateway_transaction_id' => $result['transaction_id'] ?? $payment->gateway_transaction_id,
            ]);

            if ($payment->reservation_id) {
                $payment->reservation->update(['payment_status' => 'paid']);
            }

            $this->auditPayment($payment, [
                'transaction_id' => $result['transaction_id'] ?? $transactionId,
                'transaction_status' => 'paid',
                'request_payload' => ['transaction_id' => $transactionId],
                'response_payload' => $result,
                'external_gateway_reference' => $result['transaction_id'] ?? $payment->gateway_transaction_id,
                'payment_gateway' => $payment->driver,
            ]);

            return ['success' => true, 'status' => 'paid', 'data' => $result];
        }

        $this->paymentRepository->updatePayment($payment, ['status' => 'failed', 'metadata' => $result['raw'] ?? $result]);

        $this->auditPayment($payment, [
            'transaction_id' => $result['transaction_id'] ?? $transactionId,
            'transaction_status' => 'failed',
            'request_payload' => ['transaction_id' => $transactionId],
            'response_payload' => $result,
            'external_gateway_reference' => $result['transaction_id'] ?? $payment->gateway_transaction_id,
            'payment_gateway' => $payment->driver,
        ]);

        return ['success' => false, 'status' => $status ?? 'unknown', 'data' => $result];
    }

    public function handleWebhook(WebhookResultDTO $dto): array
    {
        $payment = null;

        if ($dto->transactionId) {
            $payment = $this->paymentRepository->findByIdentifiers(null, $dto->transactionId);
        }

        if ($payment === null && $dto->orderId) {
            $payment = $this->paymentRepository->findByIdentifiers($dto->orderId, null);
        }

        if ($payment === null && $dto->paymentReference) {
            $payment = $this->paymentRepository->findByIdentifiers($dto->paymentReference, null);
        }

        if ($payment === null) {
            Log::info('Payment webhook: no matching payment', ['payload' => $dto->rawPayload]);

            $this->auditStandalone([
                'transaction_id' => $dto->transactionId ?? $dto->paymentReference ?? $dto->orderId,
                'transaction_status' => 'payment_not_found',
                'payment_gateway' => 'manual_admin',
                'request_payload' => $dto->rawPayload,
                'response_payload' => ['error' => 'payment_not_found'],
                'external_gateway_reference' => $dto->transactionId,
            ]);

            return ['success' => false, 'error' => 'payment_not_found'];
        }

        if ($payment->status === 'paid' && $dto->isPaid()) {
            $this->auditPayment($payment, [
                'transaction_id' => $dto->transactionId ?? $payment->gateway_transaction_id ?? $payment->payment_reference,
                'transaction_status' => 'already_paid',
                'payment_gateway' => $payment->driver,
                'request_payload' => $dto->rawPayload,
                'response_payload' => ['message' => 'already_processed'],
                'external_gateway_reference' => $dto->transactionId,
            ]);

            return ['success' => true, 'message' => 'already_processed'];
        }

        if ($dto->isPaid()) {
            $this->paymentRepository->updatePayment($payment, [
                'status' => 'paid',
                'metadata' => $dto->rawPayload,
                'verified_at' => now(),
                'gateway_transaction_id' => $dto->transactionId ?? $payment->gateway_transaction_id,
            ]);

            if ($payment->reservation_id) {
                $payment->reservation->update(['payment_status' => 'paid']);
            }

            $this->auditPayment($payment, [
                'transaction_id' => $dto->transactionId ?? $payment->gateway_transaction_id ?? $payment->payment_reference,
                'transaction_status' => 'paid',
                'payment_gateway' => $payment->driver,
                'request_payload' => $dto->rawPayload,
                'response_payload' => ['status' => 'paid'],
                'external_gateway_reference' => $dto->transactionId,
            ]);

            return ['success' => true];
        }

        $this->paymentRepository->updatePayment($payment, ['status' => 'failed', 'metadata' => $dto->rawPayload]);

        $this->auditPayment($payment, [
            'transaction_id' => $dto->transactionId ?? $payment->gateway_transaction_id ?? $payment->payment_reference,
            'transaction_status' => 'failed',
            'payment_gateway' => $payment->driver,
            'request_payload' => $dto->rawPayload,
            'response_payload' => ['status' => $dto->status],
            'external_gateway_reference' => $dto->transactionId,
        ]);

        return ['success' => false, 'status' => $dto->status];
    }

    /**
     * Mark a payment as failed and attach metadata for diagnostics.
     */
    public function markFailed(Payment $payment, $metadata = null): void
    {
        $this->paymentRepository->updatePayment($payment, ['status' => 'failed', 'metadata' => $metadata]);

        $this->auditPayment($payment, [
            'transaction_status' => 'failed',
            'request_payload' => null,
            'response_payload' => is_array($metadata) ? $metadata : ['metadata' => $metadata],
            'external_gateway_reference' => $payment->gateway_transaction_id,
            'payment_gateway' => $payment->driver,
        ]);
    }

    private function auditPayment(Payment $payment, array $context): void
    {
        try {
            $this->paymentAuditService->log($payment, $context);
        } catch (\Throwable $e) {
            Log::warning('Payment audit logging failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function auditStandalone(array $context): void
    {
        try {
            $this->paymentAuditService->logStandalone($context);
        } catch (\Throwable $e) {
            Log::warning('Standalone payment audit logging failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
