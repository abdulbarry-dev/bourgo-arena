<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\InitiatePaymentRequest;
use App\Http\Requests\Api\V1\VerifyPaymentRequest;
use App\Models\Payment;
use App\Services\Payment\PaymentManager;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentManager $paymentManager,
        protected PaymentService $paymentService
    ) {}

    /**
     * Initiate a payment via configured gateway.
     */
    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $dto = $request->toDTO();

        $payment = $this->paymentService->createPayment($dto);

        try {
            $result = $this->paymentService->initiate($payment, [
                'description' => $dto->description ?? 'Payment',
                'success_url' => $request->input('success_url', config('app.url')),
                'failure_url' => $request->input('failure_url', config('app.url')),
            ]);
        } catch (\Throwable $e) {
            $this->paymentService->markFailed($payment, ['error' => $e->getMessage()]);

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

        $this->paymentService->markFailed($payment, $result);

        return response()->json(['success' => false, 'error' => $result['error'] ?? 'initiation_failed'], 400);
    }

    /**
     * Verify a payment by gateway transaction id or payment_reference
     */
    public function verify(VerifyPaymentRequest $request): JsonResponse
    {
        $dto = $request->toDTO();

        $payment = $this->paymentService->findByIdentifiers($dto->paymentReference, $dto->gatewayTransactionId);

        if ($payment === null) {
            return response()->json(['success' => false, 'error' => 'payment_not_found'], 404);
        }

        try {
            $result = $this->paymentService->verify($payment);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        if (! empty($result['success'])) {
            return response()->json(['success' => true, 'status' => $result['status'] ?? 'paid', 'data' => $result['data'] ?? null]);
        }

        return response()->json(['success' => false, 'status' => $result['status'] ?? 'unknown', 'data' => $result['data'] ?? null], 400);
    }

    /**
     * Webhook endpoint for gateway callbacks.
     */
    public function webhook(Request $request, string $provider): JsonResponse
    {
        try {
            $providerInstance = $this->paymentManager->driver($provider);
        } catch (\InvalidArgumentException $e) {
            Log::warning('Webhook received for unknown provider', ['provider' => $provider]);

            return response()->json(['success' => false, 'error' => 'unknown_provider'], 400);
        }

        if (config('payment.webhooks.verify_signature', true)) {
            if (! $providerInstance->validateWebhookSignature($request)) {
                return response()->json(['success' => false, 'error' => 'invalid_signature'], 403);
            }
        }

        $dto = $providerInstance->normalizeWebhookPayload($request);

        $result = $this->paymentService->handleWebhook($dto);

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
