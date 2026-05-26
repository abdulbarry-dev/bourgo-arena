<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\InitiatePaymentRequest;
use App\Http\Requests\Api\V1\VerifyPaymentRequest;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Initiate a payment via configured gateway.
     */
    public function initiate(InitiatePaymentRequest $request, PaymentService $paymentService): JsonResponse
    {
        $dto = $request->toDTO();

        $payment = $paymentService->createPayment($dto);

        try {
            $result = $paymentService->initiate($payment, [
                'description' => $dto->description ?? 'Payment',
                'success_url' => $request->input('success_url', config('app.url')),
                'failure_url' => $request->input('failure_url', config('app.url')),
            ]);
        } catch (\Throwable $e) {
            $paymentService->markFailed($payment, ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        if (! empty($result['success'])) {
            return response()->json([
                'success' => true,
                'payment_url' => $result['payment_url'] ?? null,
                'payment_reference' => $payment->payment_reference,
                'payment_id' => $payment->id,
            ]);
        }

        $paymentService->markFailed($payment, $result);

        return response()->json(['success' => false, 'error' => $result['error'] ?? 'initiation_failed'], 400);
    }

    /**
     * Verify a payment by gateway transaction id or payment_reference
     */
    public function verify(VerifyPaymentRequest $request, PaymentService $paymentService): JsonResponse
    {
        $dto = $request->toDTO();

        $payment = $paymentService->findByIdentifiers($dto->paymentReference, $dto->gatewayTransactionId);

        if ($payment === null) {
            return response()->json(['success' => false, 'error' => 'payment_not_found'], 404);
        }

        try {
            $result = $paymentService->verify($payment);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        if (! empty($result['success'])) {
            return response()->json(['success' => true, 'status' => $result['status'] ?? 'paid', 'data' => $result['data'] ?? null]);
        }

        return response()->json(['success' => false, 'status' => $result['status'] ?? 'unknown', 'data' => $result['data'] ?? null], 400);
    }

    /**
     * Webhook endpoint for Konnect callbacks.
     */
    public function webhook(Request $request, PaymentService $paymentService): JsonResponse
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

        $result = $paymentService->handleWebhook($data);

        if (! empty($result['success'])) {
            $response = ['success' => true, 'status' => $result['status'] ?? null];

            if (! empty($result['message'])) {
                $response['message'] = $result['message'];
            }

            return response()->json($response);
        }

        $statusCode = ($result['error'] ?? null) === 'payment_not_found' ? 404 : 400;

        return response()->json(['success' => false, 'error' => $result['error'] ?? 'unknown'], $statusCode);
    }
}
