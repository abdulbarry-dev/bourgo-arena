<?php

namespace App\Services;

use App\DTOs\Payment\WebhookResultDTO;
use App\DTOs\PaymentInitiateDTO;
use App\Jobs\ReconcilePaymentJob;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Services\Payment\PaymentManager;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        protected PaymentManager $paymentManager,
        protected PaymentRepository $paymentRepository
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

        $result = $provider->initiate($payment, $options);

        if (! empty($result['success'])) {
            $this->paymentRepository->updatePayment($payment, [
                'status' => 'initiated',
                'gateway_transaction_id' => $result['gateway_transaction_id'] ?? null,
                'metadata' => $result['raw'] ?? $result,
            ]);
        } else {
            $this->paymentRepository->updatePayment($payment, ['status' => 'failed', 'metadata' => $result]);
        }

        return $result;
    }

    public function verify(Payment $payment, ?string $transactionId = null): array
    {
        $provider = $this->paymentManager->driver($payment->driver);

        $transactionId = $transactionId ?? $payment->gateway_transaction_id ?? $payment->payment_reference;

        $result = $provider->verify($transactionId);

        $status = $result['status'] ?? null;

        if (! empty($result['success']) && in_array(strtolower((string) $status), ['paid', 'completed'], true)) {
            $this->paymentRepository->updatePayment($payment, [
                'status' => 'paid',
                'metadata' => $result['raw'] ?? $result,
                'verified_at' => now(),
                'gateway_transaction_id' => $result['transaction_id'] ?? $payment->gateway_transaction_id,
            ]);

            return ['success' => true, 'status' => 'paid', 'data' => $result];
        }

        $this->paymentRepository->updatePayment($payment, ['status' => 'failed', 'metadata' => $result['raw'] ?? $result]);

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

            return ['success' => false, 'error' => 'payment_not_found'];
        }

        if ($payment->status === 'paid' && $dto->isPaid()) {
            return ['success' => true, 'message' => 'already_processed'];
        }

        if ($dto->isPaid()) {
            $dispatchSync = config('payment.webhooks.dispatch_sync', false);
            if ($dispatchSync) {
                ReconcilePaymentJob::dispatchSync($payment->id, $dto->rawPayload);
            } else {
                ReconcilePaymentJob::dispatch($payment->id, $dto->rawPayload);
            }

            return ['success' => true];
        }

        if ($dto->isRefund()) {
            $dispatchSync = config('payment.webhooks.dispatch_sync', false);
            if ($dispatchSync) {
                ReconcilePaymentJob::dispatchSync($payment->id, $dto->rawPayload);
            } else {
                ReconcilePaymentJob::dispatch($payment->id, $dto->rawPayload);
            }

            return ['success' => true, 'status' => 'refunded'];
        }

        $this->paymentRepository->updatePayment($payment, ['status' => 'failed', 'metadata' => $dto->rawPayload]);

        return ['success' => false, 'status' => $dto->status];
    }

    /**
     * Mark a payment as failed and attach metadata for diagnostics.
     */
    public function markFailed(Payment $payment, $metadata = null): void
    {
        $this->paymentRepository->updatePayment($payment, ['status' => 'failed', 'metadata' => $metadata]);
    }
}
