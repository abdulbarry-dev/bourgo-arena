<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\PaymentGatewayInterface;
use App\Events\PaymentPaid;
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
    public function initiate(InitiatePaymentRequest $request, PaymentService $paymentService, PaymentManager $paymentManager): JsonResponse
    {
        $dto = $request->toDTO();
        $providerName = $request->input('provider', config('payment.default', 'konnect'));

        $payment = $paymentService->createPayment($dto);

        try {
            $provider = $paymentManager->driver($providerName);
            $result = $provider->initiatePayment($payment, [
                'description' => $dto->description ?? 'Payment',
                'success_url' => $request->input('success_url', config('app.url')),
                'failure_url' => $request->input('failure_url', config('app.url')),
            ]);
        } catch (\Throwable $e) {
            $paymentService->markFailed($payment, ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        if (! empty($result['success'])) {
            $payment->update([
                'driver' => $provider->getName(),
                'status' => 'initiated',
                'gateway_transaction_id' => $result['gateway_transaction_id'] ?? $result['payment_id'] ?? null,
                'payment_reference' => $result['payment_reference'] ?? $payment->payment_reference,
                'metadata' => $result,
            ]);

            return response()->json([
                'success' => true,
                'payment_url' => $result['payment_url'] ?? $result['redirect_url'] ?? null,
                'payment_reference' => $payment->payment_reference,
                'payment_id' => $payment->id,
            ]);
        }

        $paymentService->markFailed($payment, $result);

        return response()->json(['success' => false, 'error' => $result['error'] ?? 'initiation_failed'], 400);
    }

    public function verify(VerifyPaymentRequest $request, PaymentService $paymentService, PaymentManager $paymentManager): JsonResponse
    {
        $dto = $request->toDTO();

        $payment = $paymentService->findByIdentifiers($dto->paymentReference, $dto->gatewayTransactionId);

        if ($payment === null) {
            return response()->json(['success' => false, 'error' => 'payment_not_found'], 404);
        }

        try {
            $provider = $this->resolveVerifyProvider($payment, $paymentManager);
            $transactionId = $dto->gatewayTransactionId
                ?? $payment->gateway_transaction_id
                ?? $payment->payment_reference;

            $result = $provider->verifyPayment($transactionId);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        $status = $result['status'] ?? null;

        if ($status && in_array(strtolower((string) $status), ['paid', 'completed'], true)) {
            $payment->update([
                'status' => 'paid',
                'metadata' => $result,
                'verified_at' => now(),
                'gateway_transaction_id' => $result['transaction_id'] ?? $payment->gateway_transaction_id,
            ]);

            PaymentPaid::dispatch($payment->fresh());

            return response()->json(['success' => true, 'status' => 'paid', 'data' => $result]);
        }

        $payment->update(['status' => 'failed', 'metadata' => $result]);

        return response()->json(['success' => false, 'status' => $status ?? 'unknown', 'data' => $result], 400);
    }

    public function webhook(Request $request, PaymentService $paymentService): JsonResponse
    {
        $payloadRaw = $request->getContent();
        $data = array_merge($request->query->all(), $request->json()->all());

        if (config('payment.webhooks.verify_signature', true)) {
            $secret = config('payment.providers.konnect.webhook_secret', config('payment.konnect.webhook_secret'));

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

    private function resolveVerifyProvider(Payment $payment, PaymentManager $paymentManager): PaymentGatewayInterface
    {
        if ($payment->driver === 'test') {
            return $paymentManager->driver('test');
        }

        return $paymentManager->driver('konnect');
    }
}
