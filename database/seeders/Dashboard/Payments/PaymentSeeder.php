<?php

namespace Database\Seeders\Dashboard\Payments;

use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $subscriptions = Subscription::query()->whereIn('payment_reference', [
            'SUB-2026-0001',
            'SUB-2026-0002',
            'SUB-2026-0003',
            'SUB-2026-0005',
            'SUB-2026-0006',
            'SUB-2026-0008',
        ])->get()->keyBy('payment_reference');

        $reservations = ApiReservation::query()->with('member')->get()->keyBy(function (ApiReservation $reservation): string {
            return $reservation->member?->email.'-'.$reservation->date->toDateString().'-'.$reservation->starts_at;
        });

        $paymentRows = [
            ['reference' => 'PAY-2026-0001', 'member_email' => 'amira.elmansouri@example.com', 'subscription_reference' => 'SUB-2026-0001', 'type' => 'subscription', 'amount' => 89.000, 'status' => 'paid', 'verified_at' => now()->subDays(20)],
            ['reference' => 'PAY-2026-0002', 'member_email' => 'othman.bennis@example.com', 'subscription_reference' => 'SUB-2026-0002', 'type' => 'subscription', 'amount' => 129.000, 'status' => 'paid', 'verified_at' => now()->subDays(11)],
            ['reference' => 'PAY-2026-0003', 'member_email' => 'lina.chafik@example.com', 'subscription_reference' => 'SUB-2026-0003', 'type' => 'subscription', 'amount' => 349.000, 'status' => 'cancelled', 'verified_at' => now()->subDays(39)],
            ['reference' => 'PAY-2026-0004', 'member_email' => 'amira.elmansouri@example.com', 'reservation_key' => 'amira.elmansouri@example.com-'.now()->addDays(1)->toDateString().'-10:00:00', 'type' => 'reservation', 'amount' => 35.000, 'status' => 'paid', 'verified_at' => now()->subDays(1)],
            ['reference' => 'PAY-2026-0005', 'member_email' => 'nadia.rachid@example.com', 'reservation_key' => 'nadia.rachid@example.com-'.now()->addDays(4)->toDateString().'-17:00:00', 'type' => 'reservation', 'amount' => 28.000, 'status' => 'cancelled', 'verified_at' => now()->subDays(2)],
            ['reference' => 'PAY-2026-0006', 'member_email' => 'mehdi.amrani@example.com', 'subscription_reference' => 'SUB-2026-0008', 'type' => 'subscription', 'amount' => 1199.000, 'status' => 'initiated', 'verified_at' => null],
        ];

        foreach ($paymentRows as $paymentRow) {
            $member = Member::query()->where('email', $paymentRow['member_email'])->first();
            $subscription = isset($paymentRow['subscription_reference']) ? ($subscriptions[$paymentRow['subscription_reference']] ?? null) : null;
            $reservation = isset($paymentRow['reservation_key']) ? ($reservations[$paymentRow['reservation_key']] ?? null) : null;

            if ($member === null) {
                continue;
            }

            Payment::query()->updateOrCreate(
                ['payment_reference' => $paymentRow['reference']],
                [
                    'member_id' => $member->id,
                    'reservation_id' => $reservation?->id,
                    'subscription_id' => $subscription?->id,
                    'driver' => 'konnect',
                    'gateway' => 'konnect',
                    'type' => $paymentRow['type'],
                    'amount' => $paymentRow['amount'],
                    'currency' => 'TND',
                    'status' => $paymentRow['status'],
                    'gateway_transaction_id' => 'gw-'.$paymentRow['reference'],
                    'metadata' => ['source' => 'seed'],
                    'verified_at' => $paymentRow['verified_at'],
                ],
            );
        }
    }
}
