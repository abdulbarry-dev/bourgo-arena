<?php

namespace App\Jobs;

use App\Events\PaymentReconciled;
use App\Events\PaymentReconcileFailed;
use App\Models\Payment;
use App\Models\PaymentReconciliation;
use App\Services\LoyaltyCalculatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReconcilePaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $paymentId;

    public array $payload;

    /**
     * Number of times to attempt the job.
     */
    public int $tries = 5;

    /**
     * Backoff intervals in seconds for retries.
     */
    public array $backoff = [60, 300, 900];

    /**
     * Create a new job instance.
     */
    public function __construct(int $paymentId, array $payload = [])
    {
        $this->paymentId = $paymentId;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(LoyaltyCalculatorService $loyaltyCalculatorService): void
    {
        $payment = Payment::query()->lockForUpdate()->find($this->paymentId);

        if (! $payment) {
            Log::warning('ReconcilePaymentJob: payment not found', ['payment_id' => $this->paymentId]);

            return;
        }

        $data = $this->payload;
        $status = strtolower((string) ($data['status'] ?? $data['transaction_status'] ?? ''));

        if ($status && in_array($status, ['paid', 'completed', 'success'], true)) {
            if ($payment->status === 'paid') {
                return;
            }

            $payment->update([
                'status' => 'paid',
                'gateway_transaction_id' => $data['payment_id'] ?? $data['paymentRef'] ?? $payment->gateway_transaction_id,
                'metadata' => $data,
                'verified_at' => now(),
                'reconciled_at' => $payment->reconciled_at ?? now(),
                'reconciled_by' => $payment->reconciled_by ?? null,
            ]);

            if ($payment->reservation_id) {
                $reservation = $payment->reservation()->first();
                if ($reservation) {
                    $reservation->update(['payment_status' => 'paid']);
                    try {
                        $loyaltyCalculatorService->creditVariableForReservation($reservation);
                    } catch (Throwable $e) {
                        Log::error('ReconcilePaymentJob: loyalty credit failed', ['error' => $e->getMessage()]);
                    }
                }
            }

            if ($payment->subscription_id) {
                $subscription = $payment->subscription()->first();
                if ($subscription) {
                    $subscription->update(['amount_paid' => $payment->amount, 'payment_reference' => $payment->payment_reference]);
                    try {
                        $loyaltyCalculatorService->creditFixedMonthlyRenewal($subscription);
                    } catch (Throwable $e) {
                        Log::error('ReconcilePaymentJob: subscription loyalty failed', ['error' => $e->getMessage()]);
                    }
                }
            }

            // Emit a reconciled event for monitoring/notifications
            try {
                event(new PaymentReconciled($payment, $data));
            } catch (Throwable $e) {
                Log::warning('ReconcilePaymentJob: emitting PaymentReconciled failed', ['error' => $e->getMessage()]);
            }

            // create reconciliation history row
            try {
                PaymentReconciliation::create([
                    'payment_id' => $payment->id,
                    'admin_id' => null,
                    'type' => 'reconciled',
                    'amount' => null,
                    'metadata' => $data,
                ]);
            } catch (Throwable $e) {
                Log::warning('ReconcilePaymentJob: creating PaymentReconciliation failed', ['error' => $e->getMessage()]);
            }

            return;
        }

        if (in_array($status, ['refunded', 'refund', 'partially_refunded', 'partial_refund', 'refunded_partially'], true)) {
            $refundAmount = $data['refund_amount'] ?? $data['amount_refunded'] ?? $data['refund']['amount'] ?? null;

            $payment->update([
                'status' => 'refunded',
                'metadata' => array_merge($payment->metadata ?? [], $data),
            ]);

            // create refund reconciliation row
            try {
                PaymentReconciliation::create([
                    'payment_id' => $payment->id,
                    'admin_id' => null,
                    'type' => 'refunded',
                    'amount' => $refundAmount !== null ? (float) $refundAmount : null,
                    'metadata' => $data,
                ]);
            } catch (Throwable $e) {
                Log::warning('ReconcilePaymentJob: creating refund PaymentReconciliation failed', ['error' => $e->getMessage()]);
            }

            try {
                event(new PaymentReconciled($payment, $data));
            } catch (Throwable $e) {
                Log::warning('ReconcilePaymentJob: emitting PaymentReconciled after refund failed', ['error' => $e->getMessage()]);
            }

            if ($refundAmount !== null) {
                $payment->update(['metadata' => array_merge($payment->metadata ?? [], ['refund_amount' => $refundAmount])]);
            }

            return;
        }

        // unhandled: mark failed

        $payment->update(['status' => 'failed', 'metadata' => array_merge($payment->metadata ?? [], $data)]);

        try {
            event(new PaymentReconcileFailed($payment->id, 'unhandled_status', $data));
        } catch (Throwable $e) {
            Log::warning('ReconcilePaymentJob: emitting PaymentReconcileFailed failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle a job failure and flag the payment for manual review.
     */
    public function failed(Throwable $exception): void
    {
        try {
            $payment = Payment::find($this->paymentId);
            if ($payment) {
                $payment->update(['status' => 'failed', 'metadata' => array_merge($payment->metadata ?? [], ['reconcile_error' => $exception->getMessage()])]);
            }

            event(new PaymentReconcileFailed($this->paymentId, $exception->getMessage(), []));
        } catch (Throwable $e) {
            // avoid throwing from failed()
        }

        Log::error('ReconcilePaymentJob failed', ['payment_id' => $this->paymentId, 'error' => $exception->getMessage()]);
    }
}
