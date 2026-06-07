<?php

namespace App\Repositories;

use App\Models\Payment;

class PaymentRepository
{
    /**
     * Find a payment by its reference or gateway transaction ID.
     */
    public function findByIdentifiers(?string $paymentReference = null, ?string $gatewayTransactionId = null): ?Payment
    {
        if (! empty($paymentReference)) {
            $payment = Payment::where('payment_reference', $paymentReference)->first();

            if ($payment) {
                return $payment;
            }
        }

        if (! empty($gatewayTransactionId)) {
            return Payment::where('gateway_transaction_id', $gatewayTransactionId)->first();
        }

        return null;
    }

    /**
     * Create a new payment record.
     */
    public function createPayment(array $data): Payment
    {
        return Payment::create($data);
    }

    /**
     * Update an existing payment.
     */
    public function updatePayment(Payment $payment, array $data): bool
    {
        return $payment->update($data);
    }
}
