<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ReconcilePaymentJob;
use App\Models\Payment;
use App\Services\PaymentGateway\KonnectGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Initiate a payment via configured gateway.
     */
    public function initiate(Request $request, KonnectGateway $konnectGateway): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'reservation_id' => ['nullable', 'integer', 'exists:api_reservations,id'],
            'subscription_id' => ['nullable', 'integer', 'exists:subscriptions,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
        ]);

        $payment = Payment::create([
            'member_id' => $data['member_id'] ?? null,
            'reservation_id' => $data['reservation_id'] ?? null,
            'subscription_id' => $data['subscription_id'] ?? null,
            'driver' => 'konnect',
            'type' => $data['type'] ?? 'reservation',
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'TND',
            'status' => 'pending',
            'payment_reference' => 'konnect_'.bin2hex(random_bytes(6)),
            'metadata' => null,
        ]);

        // Prepare gateway payload
        $payload = [
            'amount' => (float) $payment->amount,
            'description' => $data['description'] ?? 'Payment',
            'payment_reference' => $payment->payment_reference,
            'success_url' => $request->input('success_url', config('app.url')),
            'failure_url' => $request->input('failure_url', config('app.url')),
        ];

        try {
            $result = $konnectGateway->initiate($payload);
        } catch (\Throwable $e) {
            $payment->update(['status' => 'failed', 'metadata' => ['error' => $e->getMessage()]]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        if (! empty($result['success'])) {
            $payment->update([
                'status' => 'initiated',
                'gateway_transaction_id' => $result['gateway_transaction_id'] ?? null,
                'metadata' => $result,
            ]);

            return response()->json([
                'success' => true,
                'payment_url' => $result['payment_url'] ?? null,
                'payment_reference' => $payment->payment_reference,
                'payment_id' => $payment->id,
            ]);
        }

        $payment->update(['status' => 'failed', 'metadata' => $result]);

        return response()->json(['success' => false, 'error' => $result['error'] ?? 'initiation_failed'], 400);
    }

    /**
     * Verify a payment by gateway transaction id or payment_reference
     */
    public function verify(Request $request, KonnectGateway $konnectGateway): JsonResponse
    {
        $data = $request->validate([
            'payment_reference' => ['nullable', 'string'],
            'gateway_transaction_id' => ['nullable', 'string'],
        ]);

        $payment = null;

        if (! empty($data['payment_reference'])) {
            $payment = Payment::where('payment_reference', $data['payment_reference'])->first();
        }

        if ($payment === null && ! empty($data['gateway_transaction_id'])) {
            $payment = Payment::where('gateway_transaction_id', $data['gateway_transaction_id'])->first();
        }

        if ($payment === null) {
            return response()->json(['success' => false, 'error' => 'payment_not_found'], 404);
        }

        try {
            $transactionId = $payment->gateway_transaction_id ?? $payment->payment_reference;
            $result = $konnectGateway->verify($transactionId);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        $status = $result['status'] ?? null;

        if ($status && in_array(strtolower($status), ['paid', 'completed'], true)) {
            $payment->update([
                'status' => 'paid',
                'metadata' => $result,
                'verified_at' => now(),
                'gateway_transaction_id' => $result['transaction_id'] ?? $payment->gateway_transaction_id ?? $result['transaction_id'] ?? $payment->gateway_transaction_id,
            ]);

            return response()->json(['success' => true, 'status' => 'paid', 'data' => $result]);
        }

        $payment->update(['status' => 'failed', 'metadata' => $result]);

        return response()->json(['success' => false, 'status' => $status ?? 'unknown', 'data' => $result], 400);
    }

    /**
     * Webhook endpoint for Konnect callbacks.
     */
    public function webhook(Request $request, KonnectGateway $konnectGateway): JsonResponse
    {
        $payloadRaw = $request->getContent();
        $data = array_merge($request->query->all(), $request->json()->all());

        if (config('payment.webhooks.verify_signature', true)) {
            $secret = config('payment.konnect.webhook_secret');

            if (empty($secret)) {
                Log::warning('Webhook secret not configured for Konnect; falling back to verification by reference');
            }

            $headerNames = ['X-Konnect-Signature', 'X-konnect-Signature', 'X-Signature', 'X-Signature-256'];
            $signature = null;
            foreach ($headerNames as $h) {
                $val = $request->header($h);
                if (! empty($val)) {
                    $signature = $val;
                    break;
                }
            }

            if (empty($signature)) {
                Log::warning('Missing Konnect webhook signature header');

                return response()->json(['success' => false, 'error' => 'missing_signature'], 403);
            }

            if (! empty($secret)) {
                $expected = hash_hmac('sha256', $payloadRaw, $secret);
                if (! hash_equals($expected, $signature)) {
                    Log::warning('Invalid Konnect webhook signature');

                    return response()->json(['success' => false, 'error' => 'invalid_signature'], 403);
                }
            }
        }

        if (! empty($data['payment_ref'])) {
            try {
                $verifyResult = $konnectGateway->verify($data['payment_ref']);
                $data = is_array($verifyResult) ? $verifyResult : ['status' => $verifyResult];
                $data['payment_reference'] = $data['payment_reference'] ?? $data['paymentRef'] ?? $data['payment_ref'] ?? $data['order_id'] ?? $data['token'] ?? $data['id'] ?? null;
            } catch (\Throwable $e) {
                Log::error('Konnect verify failed during webhook handling', ['error' => $e->getMessage()]);

                return response()->json(['success' => false, 'error' => 'verify_failed'], 500);
            }
        }

        $transactionId = $data['paymentRef'] ?? $data['payment_id'] ?? $data['transaction_id'] ?? null;
        $orderId = $data['order_id'] ?? $data['token'] ?? $data['orderId'] ?? $data['payment_reference'] ?? null;

        $payment = null;

        if ($transactionId) {
            $payment = Payment::where('gateway_transaction_id', $transactionId)->first();
        }

        if ($payment === null && $orderId) {
            $payment = Payment::where('payment_reference', $orderId)->first();
        }

        if ($payment === null) {
            // attempt fallback: search by payment_reference present in payload
            if (! empty($data['payment_reference'])) {
                $payment = Payment::where('payment_reference', $data['payment_reference'])->first();
            }
        }

        if ($payment === null) {
            Log::info('Konnect webhook received but no matching payment found', ['payload' => $data]);

            return response()->json(['success' => false, 'error' => 'payment_not_found'], 404);
        }

        $status = strtolower((string) ($data['status'] ?? $data['transaction_status'] ?? ''));

        if ($payment->status === 'paid' && in_array($status, ['paid', 'completed', 'success'], true)) {
            return response()->json(['success' => true, 'message' => 'already_processed']);
        }

        if (in_array($status, ['paid', 'completed', 'success'], true)) {
            $dispatchSync = config('payment.webhooks.dispatch_sync', false);
            if ($dispatchSync) {
                ReconcilePaymentJob::dispatchSync($payment->id, $data);
            } else {
                ReconcilePaymentJob::dispatch($payment->id, $data);
            }

            return response()->json(['success' => true]);
        }

        if (in_array($status, ['refunded', 'refund', 'partially_refunded', 'partial_refund', 'refunded_partially'], true)) {
            $dispatchSync = config('payment.webhooks.dispatch_sync', false);
            if ($dispatchSync) {
                ReconcilePaymentJob::dispatchSync($payment->id, $data);
            } else {
                ReconcilePaymentJob::dispatch($payment->id, $data);
            }

            return response()->json(['success' => true, 'status' => 'refunded']);
        }

        $payment->update(['status' => 'failed', 'metadata' => $data]);

        return response()->json(['success' => false, 'status' => $status]);
    }
}
