<?php

namespace App\Repositories;

use App\Models\Payment;
use Illuminate\Validation\ValidationException;

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
        // Prevent mutating completed payments for audit integrity
        if ($payment->status === 'paid') {
            throw ValidationException::withMessages(['payment' => ['Cannot modify a completed payment.']]);
        }

        // Prevent changing gateway driver after initiation
        if (isset($data['driver']) && $payment->status !== 'pending') {
            throw ValidationException::withMessages(['driver' => ['Payment gateway cannot be changed after initiation.']]);
        }

        return $payment->update($data);
    }
}
