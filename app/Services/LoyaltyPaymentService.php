<?php

namespace App\Services;

use App\Events\PaymentPaid;
use App\Models\ApiReservation;
use App\Models\LoyaltyAuditLog;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoyaltyPaymentService
{
    public function __construct(
        protected GeoLocationService $geoLocationService,
    ) {}

    /**
     * Pay for a reservation or subscription using loyalty points.
     *
     * @throws ValidationException
     */
    public function pay(Member $member, string $type, int $id, ?Request $request = null): Payment
    {
        $request ??= request();

        return DB::transaction(function () use ($member, $type, $id, $request) {
            $item = $this->resolveAndLockItem($member, $type, $id);
            $amountTnd = $this->getAmountTnd($type, $item);
            $pointsNeeded = $this->calculatePointsNeeded($amountTnd);

            $config = config('loyalty.points_to_tnd');
            $minimumPoints = (int) ($config['minimum_payment_points'] ?? 100);
            $maximumPoints = (int) ($config['maximum_per_transaction'] ?? 10000);

            if ($pointsNeeded < $minimumPoints) {
                throw ValidationException::withMessages([
                    'points' => [__('A minimum of :points loyalty points is required for payment.', ['points' => $minimumPoints])],
                ])->errorBag('below_minimum_points');
            }

            if ($pointsNeeded > $maximumPoints) {
                throw ValidationException::withMessages([
                    'points' => [__('This payment exceeds the maximum of :points loyalty points per transaction.', ['points' => $maximumPoints])],
                ])->errorBag('above_maximum_points');
            }

            $member = Member::query()->lockForUpdate()->findOrFail($member->id);

            if ((int) $member->loyalty_points < $pointsNeeded) {
                throw ValidationException::withMessages([
                    'points' => [__('Insufficient loyalty points. You need :needed more points.', ['needed' => $pointsNeeded - (int) $member->loyalty_points])],
                ])->errorBag('insufficient_points');
            }

            $balanceBefore = (int) $member->loyalty_points;

            $member->decrement('loyalty_points', $pointsNeeded);
            $balanceAfter = (int) $member->fresh()->loyalty_points;

            $geo = $this->geoLocationService->detect($request);

            LoyaltyPoint::create([
                'member_id' => $member->id,
                'points' => -$pointsNeeded,
                'transaction_type' => 'payment',
                'source_type' => $item->getMorphClass(),
                'source_id' => $id,
                'idempotency_key' => 'loyalty_payment:'.$type.':'.$id.':'.$member->id,
            ]);

            LoyaltyAuditLog::create([
                'member_id' => $member->id,
                'action' => 'payment',
                'points_changed' => -$pointsNeeded,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source_type' => $item->getMorphClass(),
                'source_id' => $id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'country_code' => $geo->countryCode,
                    'country_name' => $geo->countryName,
                    'city' => $geo->city,
                    'amount_tnd' => $amountTnd,
                    'points_used' => $pointsNeeded,
                    'conversion_rate' => (int) ($config['rate'] ?? 100),
                ],
            ]);

            $payment = Payment::create([
                'member_id' => $member->id,
                'reservation_id' => $type === 'reservation' ? $id : null,
                'subscription_id' => $type === 'subscription' ? $id : null,
                'driver' => 'loyalty',
                'gateway' => 'loyalty_points',
                'type' => $type,
                'amount' => $amountTnd,
                'status' => 'paid',
                'payment_reference' => 'loyalty_'.Str::random(32),
                'ip_address' => $request->ip(),
                'country_code' => $geo->countryCode,
                'city' => $geo->city,
            ]);

            $this->markItemPaid($type, $item);

            PaymentPaid::dispatch($payment->fresh());

            return $payment;
        });
    }

    private function resolveAndLockItem(Member $member, string $type, int $id): ApiReservation|Subscription
    {
        if ($type === 'reservation') {
            $reservation = ApiReservation::query()
                ->lockForUpdate()
                ->find($id);

            if ($reservation === null) {
                throw ValidationException::withMessages([
                    'reservation_id' => [__('Reservation not found.')],
                ]);
            }

            if ($reservation->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'reservation_id' => [__('This reservation has been cancelled.')],
                ]);
            }

            if ($reservation->payment_status === 'paid') {
                throw ValidationException::withMessages([
                    'reservation_id' => [__('This reservation has already been paid.')],
                ])->errorBag('already_paid');
            }

            return $reservation;
        }

        $subscription = Subscription::query()
            ->lockForUpdate()
            ->find($id);

        if ($subscription === null) {
            throw ValidationException::withMessages([
                'subscription_id' => [__('Subscription not found.')],
            ]);
        }

        if ($subscription->status === 'cancelled') {
            throw ValidationException::withMessages([
                'subscription_id' => [__('This subscription has been cancelled.')],
            ]);
        }

        if ($subscription->status === 'active') {
            throw ValidationException::withMessages([
                'subscription_id' => [__('This subscription is already active.')],
            ])->errorBag('already_paid');
        }

        $alreadyPaidViaLoyalty = $subscription->payments()
            ->where('driver', 'loyalty')
            ->where('status', 'paid')
            ->exists();

        if ($alreadyPaidViaLoyalty) {
            throw ValidationException::withMessages([
                'subscription_id' => [__('This subscription has already been paid.')],
            ])->errorBag('already_paid');
        }

        return $subscription;
    }

    private function getAmountTnd(string $type, ApiReservation|Subscription $item): float
    {
        if ($type === 'reservation') {
            return (float) ($item->activity?->base_price ?? 0);
        }

        return (float) ($item->plan?->price ?? 0);
    }

    /**
     * Calculate points needed for a given TND amount.
     * Uses the configured conversion rate (points = TND × rate).
     */
    public function calculatePointsNeeded(float $amountTnd): int
    {
        $rate = (int) (config('loyalty.points_to_tnd.rate') ?? 100);

        return (int) ceil($amountTnd * $rate);
    }

    public function getTndEquivalent(int $points): float
    {
        $rate = (int) (config('loyalty.points_to_tnd.rate') ?? 100);

        return round($points / $rate, 3);
    }

    private function markItemPaid(string $type, ApiReservation|Subscription $item): void
    {
        if ($type === 'reservation' && $item instanceof ApiReservation) {
            $item->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ]);
        }

        if ($type === 'subscription' && $item instanceof Subscription) {
            $item->update(['payment_method' => 'loyalty_points', 'payment_reference' => 'loyalty_payment']);
        }
    }
}
