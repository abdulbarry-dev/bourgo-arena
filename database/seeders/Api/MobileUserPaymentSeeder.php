<?php

namespace Database\Seeders\Api;

use App\Models\ApiReservation;
use App\Models\LoyaltyAuditLog;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;

class MobileUserPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $member = Member::where('email', 'abdelbariguenichi@gmail.com')->first();
        $user = User::where('email', 'abdelbariguenichi@gmail.com')->first();

        if ($member === null || $user === null) {
            return;
        }

        $subscription = Subscription::where('member_id', $member->id)->first();
        $paidReservations = ApiReservation::where('member_id', $member->id)
            ->where('status', 'confirmed')
            ->get();

        if ($subscription === null || $paidReservations->isEmpty()) {
            return;
        }

        $reservation = $paidReservations->first();
        $secondReservation = $paidReservations->skip(1)->first() ?? $reservation;

        $this->seedKonnectPayments($member, $user, $subscription, $reservation);
        $this->seedManualPayments($member, $user, $subscription, $secondReservation);
        $this->seedLoyaltyPayments($member, $subscription, $reservation, $secondReservation);
    }

    private function seedKonnectPayments(Member $member, User $user, Subscription $subscription, ApiReservation $reservation): void
    {
        if (Payment::where('payment_reference', 'PAY-KONNECT-SUB-001')->exists()) {
            return;
        }

        Payment::updateOrCreate(
            ['payment_reference' => 'PAY-KONNECT-SUB-001'],
            [
                'member_id' => $member->id,
                'subscription_id' => $subscription->id,
                'driver' => 'konnect',
                'gateway' => 'konnect',
                'type' => 'subscription',
                'amount' => 129.000,
                'status' => 'paid',
                'gateway_transaction_id' => 'gw-konnect-sub-001',
                'metadata' => ['source' => 'seed'],
                'verified_at' => now()->subDays(10),
                'ip_address' => '196.179.1.10',
                'country_code' => 'TN',
            ]
        );

        PaymentTransaction::updateOrCreate(
            ['transaction_id' => 'TXN-KONNECT-SUB-001'],
            [
                'user_id' => $user->id,
                'amount' => 129.000,
                'payment_gateway' => 'konnect',
                'transaction_status' => 'success',
                'external_gateway_reference' => 'gw-konnect-sub-001',
                'ip_address' => '196.179.1.10',
                'user_agent' => 'Mozilla/5.0 (Mobile)',
                'request_payload' => [
                    'amount' => '129.000',
                    'description' => 'Performance Monthly subscription via Konnect',
                ],
                'response_payload' => [
                    'status' => 'paid',
                    'transaction_id' => 'gw-konnect-sub-001',
                ],
            ]
        );

        Payment::updateOrCreate(
            ['payment_reference' => 'PAY-KONNECT-RES-001'],
            [
                'member_id' => $member->id,
                'reservation_id' => $reservation->id,
                'driver' => 'konnect',
                'gateway' => 'konnect',
                'type' => 'reservation',
                'amount' => 35.000,
                'status' => 'paid',
                'gateway_transaction_id' => 'gw-konnect-res-001',
                'metadata' => ['source' => 'seed'],
                'verified_at' => now()->subDays(5),
                'ip_address' => '196.179.1.10',
                'country_code' => 'TN',
            ]
        );

        PaymentTransaction::updateOrCreate(
            ['transaction_id' => 'TXN-KONNECT-RES-001'],
            [
                'user_id' => $user->id,
                'reservation_id' => $reservation->id,
                'amount' => 35.000,
                'payment_gateway' => 'konnect',
                'transaction_status' => 'success',
                'external_gateway_reference' => 'gw-konnect-res-001',
                'ip_address' => '196.179.1.10',
                'user_agent' => 'Mozilla/5.0 (Mobile)',
                'request_payload' => [
                    'amount' => '35.000',
                    'description' => 'Activity reservation via Konnect',
                ],
                'response_payload' => [
                    'status' => 'paid',
                    'transaction_id' => 'gw-konnect-res-001',
                ],
            ]
        );
    }

    private function seedManualPayments(Member $member, User $user, Subscription $subscription, ApiReservation $reservation): void
    {
        if (PaymentTransaction::where('transaction_id', 'TXN-MANUAL-SUB-001')->exists()) {
            return;
        }

        Payment::updateOrCreate(
            ['payment_reference' => 'PAY-MANUAL-SUB-001'],
            [
                'member_id' => $member->id,
                'subscription_id' => $subscription->id,
                'driver' => 'konnect',
                'gateway' => 'konnect',
                'type' => 'subscription',
                'amount' => 129.000,
                'status' => 'paid',
                'gateway_transaction_id' => 'gw-manual-sub-001',
                'metadata' => ['source' => 'seed', 'confirmed_by' => 'admin'],
                'verified_at' => now()->subDays(8),
                'ip_address' => '127.0.0.1',
                'country_code' => 'TN',
            ]
        );

        PaymentTransaction::updateOrCreate(
            ['transaction_id' => 'TXN-MANUAL-SUB-001'],
            [
                'user_id' => $user->id,
                'amount' => 129.000,
                'payment_gateway' => 'manual_admin',
                'transaction_status' => 'success',
                'external_gateway_reference' => 'admin-confirm-sub-001',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Admin Dashboard',
                'request_payload' => [
                    'amount' => '129.000',
                    'confirmed_by' => 'admin',
                    'method' => 'manual',
                ],
                'response_payload' => [
                    'status' => 'confirmed',
                    'message' => 'Manually confirmed by admin',
                ],
            ]
        );

        Payment::updateOrCreate(
            ['payment_reference' => 'PAY-MANUAL-RES-001'],
            [
                'member_id' => $member->id,
                'reservation_id' => $reservation->id,
                'driver' => 'konnect',
                'gateway' => 'konnect',
                'type' => 'reservation',
                'amount' => 35.000,
                'status' => 'paid',
                'gateway_transaction_id' => 'gw-manual-res-001',
                'metadata' => ['source' => 'seed', 'confirmed_by' => 'admin'],
                'verified_at' => now()->subDays(3),
                'ip_address' => '127.0.0.1',
                'country_code' => 'TN',
            ]
        );

        PaymentTransaction::updateOrCreate(
            ['transaction_id' => 'TXN-MANUAL-RES-001'],
            [
                'user_id' => $user->id,
                'reservation_id' => $reservation->id,
                'amount' => 35.000,
                'payment_gateway' => 'manual_admin',
                'transaction_status' => 'success',
                'external_gateway_reference' => 'admin-confirm-res-001',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Admin Dashboard',
                'request_payload' => [
                    'amount' => '35.000',
                    'confirmed_by' => 'admin',
                    'method' => 'manual',
                ],
                'response_payload' => [
                    'status' => 'confirmed',
                    'message' => 'Manually confirmed by admin',
                ],
            ]
        );
    }

    private function seedLoyaltyPayments(Member $member, Subscription $subscription, ApiReservation $reservation, ApiReservation $secondReservation): void
    {
        if (Payment::where('payment_reference', 'PAY-LOYALTY-SUB-001')->exists()) {
            return;
        }

        $startBalance = 20000;
        $member->update(['loyalty_points' => $startBalance]);

        $pointsSub = 8900;
        $balanceAfterSub = $startBalance - $pointsSub;

        Payment::updateOrCreate(
            ['payment_reference' => 'PAY-LOYALTY-SUB-001'],
            [
                'member_id' => $member->id,
                'subscription_id' => $subscription->id,
                'driver' => 'loyalty',
                'gateway' => 'loyalty_points',
                'type' => 'subscription',
                'amount' => 89.000,
                'status' => 'paid',
                'verified_at' => now()->subDays(19),
                'ip_address' => '196.179.1.10',
                'country_code' => 'TN',
            ]
        );

        LoyaltyPoint::updateOrCreate(
            ['idempotency_key' => 'seed-loyalty-payment-sub-001'],
            [
                'member_id' => $member->id,
                'points' => -$pointsSub,
                'transaction_type' => 'payment',
                'source_type' => 'subscription',
                'source_id' => $subscription->id,
                'created_at' => now()->subDays(19),
            ]
        );

        LoyaltyAuditLog::updateOrCreate(
            [
                'member_id' => $member->id,
                'action' => 'payment',
                'source_type' => 'subscription',
                'source_id' => $subscription->id,
                'points_changed' => -$pointsSub,
            ],
            [
                'balance_before' => $startBalance,
                'balance_after' => $balanceAfterSub,
                'ip_address' => '196.179.1.10',
                'user_agent' => 'Bourgo Arena/1.0 (Mobile)',
                'metadata' => [
                    'amount_tnd' => 89.000,
                    'points_used' => $pointsSub,
                    'conversion_rate' => 100,
                ],
                'created_at' => now()->subDays(19),
            ]
        );

        $pointsRes = 3500;
        $balanceAfterRes = $balanceAfterSub - $pointsRes;

        Payment::updateOrCreate(
            ['payment_reference' => 'PAY-LOYALTY-RES-001'],
            [
                'member_id' => $member->id,
                'reservation_id' => $reservation->id,
                'driver' => 'loyalty',
                'gateway' => 'loyalty_points',
                'type' => 'reservation',
                'amount' => 35.000,
                'status' => 'paid',
                'verified_at' => now()->subDays(12),
                'ip_address' => '196.179.1.10',
                'country_code' => 'TN',
            ]
        );

        LoyaltyPoint::updateOrCreate(
            ['idempotency_key' => 'seed-loyalty-payment-res-001'],
            [
                'member_id' => $member->id,
                'points' => -$pointsRes,
                'transaction_type' => 'payment',
                'source_type' => 'reservation',
                'source_id' => $reservation->id,
                'created_at' => now()->subDays(12),
            ]
        );

        LoyaltyAuditLog::updateOrCreate(
            [
                'member_id' => $member->id,
                'action' => 'payment',
                'source_type' => 'reservation',
                'source_id' => $reservation->id,
                'points_changed' => -$pointsRes,
            ],
            [
                'balance_before' => $balanceAfterSub,
                'balance_after' => $balanceAfterRes,
                'ip_address' => '196.179.1.10',
                'user_agent' => 'Bourgo Arena/1.0 (Mobile)',
                'metadata' => [
                    'amount_tnd' => 35.000,
                    'points_used' => $pointsRes,
                    'conversion_rate' => 100,
                ],
                'created_at' => now()->subDays(12),
            ]
        );

        $member->update(['loyalty_points' => $balanceAfterRes]);
    }
}
