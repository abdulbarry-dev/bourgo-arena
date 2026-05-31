<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PaymentAuditService
{
    public function log(Payment $payment, array $context = [], ?Request $request = null): PaymentTransaction
    {
        $request ??= request();

        $reservation = $payment->reservation;
        $requestUser = $request?->user();
        $resolvedUser = $context['user'] ?? $requestUser;

        if (! $resolvedUser instanceof User && ! empty($context['user_id'])) {
            $resolvedUser = User::find($context['user_id']);
        }

        $userInformation = $context['user_information'] ?? array_filter([
            'id' => $resolvedUser?->id,
            'name' => $resolvedUser?->name,
            'email' => $resolvedUser?->email,
            'phone' => $resolvedUser?->phone,
            'member_id' => $payment->member_id,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $reservationDetails = $context['reservation_details'] ?? ($reservation
            ? Arr::only($reservation->toArray(), [
                'id',
                'activity_id',
                'activity_slot_id',
                'date',
                'starts_at',
                'ends_at',
                'status',
                'payment_status',
                'price',
            ])
            : null);

        $refundDetails = $context['refund_details'] ?? array_filter([
            'status' => $context['refund_status'] ?? null,
            'amount' => $context['refund_amount'] ?? null,
            'reference' => $context['refund_reference'] ?? null,
            'refunded_at' => $context['refunded_at'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return PaymentTransaction::create([
            'transaction_id' => (string) ($context['transaction_id']
                ?? $payment->gateway_transaction_id
                ?? $payment->payment_reference
                ?? Str::uuid()),
            'user_id' => $this->resolveUserId($context['user_id'] ?? $resolvedUser?->id),
            'reservation_id' => $context['reservation_id'] ?? $payment->reservation_id,
            'amount' => (float) ($context['amount'] ?? $payment->amount),
            'currency' => strtoupper((string) ($context['currency'] ?? $payment->currency ?? 'TND')),
            'payment_gateway' => (string) ($context['payment_gateway'] ?? $payment->driver ?? 'manual_admin'),
            'transaction_status' => (string) ($context['transaction_status'] ?? $payment->status ?? 'pending'),
            'external_gateway_reference' => $context['external_gateway_reference']
                ?? $payment->gateway_transaction_id
                ?? null,
            'reservation_details' => $reservationDetails,
            'user_information' => empty($userInformation) ? null : $userInformation,
            'refund_status' => (string) ($context['refund_status'] ?? 'not_requested'),
            'refund_amount' => $context['refund_amount'] ?? null,
            'refund_reference' => $context['refund_reference'] ?? null,
            'refunded_at' => $context['refunded_at'] ?? null,
            'refund_details' => empty($refundDetails) ? null : $refundDetails,
            'ip_address' => $context['ip_address'] ?? $request?->ip(),
            'user_agent' => $context['user_agent'] ?? $request?->userAgent(),
            'request_payload' => $this->normalizePayload($context['request_payload'] ?? null),
            'response_payload' => $this->normalizePayload($context['response_payload'] ?? null),
        ]);
    }

    /**
     * Log an audit row even when no Payment model could be matched.
     */
    public function logStandalone(array $context = [], ?Request $request = null): PaymentTransaction
    {
        $request ??= request();

        return PaymentTransaction::create([
            'transaction_id' => (string) ($context['transaction_id'] ?? Str::uuid()),
            'user_id' => $this->resolveUserId($context['user_id'] ?? $request?->user()?->id),
            'reservation_id' => $context['reservation_id'] ?? null,
            'amount' => (float) ($context['amount'] ?? 0),
            'currency' => strtoupper((string) ($context['currency'] ?? 'TND')),
            'payment_gateway' => (string) ($context['payment_gateway'] ?? 'manual_admin'),
            'transaction_status' => (string) ($context['transaction_status'] ?? 'unknown'),
            'external_gateway_reference' => $context['external_gateway_reference'] ?? null,
            'reservation_details' => $this->normalizePayload($context['reservation_details'] ?? null),
            'user_information' => $this->normalizePayload($context['user_information'] ?? null),
            'refund_status' => (string) ($context['refund_status'] ?? 'not_requested'),
            'refund_amount' => $context['refund_amount'] ?? null,
            'refund_reference' => $context['refund_reference'] ?? null,
            'refunded_at' => $context['refunded_at'] ?? null,
            'refund_details' => $this->normalizePayload($context['refund_details'] ?? null),
            'ip_address' => $context['ip_address'] ?? $request?->ip(),
            'user_agent' => $context['user_agent'] ?? $request?->userAgent(),
            'request_payload' => $this->normalizePayload($context['request_payload'] ?? null),
            'response_payload' => $this->normalizePayload($context['response_payload'] ?? null),
        ]);
    }

    private function normalizePayload(mixed $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        if (is_array($payload)) {
            return $payload;
        }

        return ['value' => $payload];
    }

    private function resolveUserId(mixed $userId): ?int
    {
        if (! is_int($userId) && ! ctype_digit((string) $userId)) {
            return null;
        }

        $resolvedUserId = (int) $userId;

        return User::query()->whereKey($resolvedUserId)->exists() ? $resolvedUserId : null;
    }
}
