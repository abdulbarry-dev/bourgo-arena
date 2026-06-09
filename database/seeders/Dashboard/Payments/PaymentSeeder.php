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
        if (Payment::count() > 10) {
            return;
        }

        $subscriptions = Subscription::query()->whereIn('payment_reference', [
            'SUB-2026-0001', 'SUB-2026-0002', 'SUB-2026-0003',
            'SUB-2026-0005', 'SUB-2026-0006', 'SUB-2026-0008',
        ])->get()->keyBy('payment_reference');

        $reservations = ApiReservation::query()->with('member')->get();

        // --- Konnect payments (existing logic) ---
        $paymentRows = [
            ['ref' => 'PAY-2026-0001', 'email' => 'amira.elmansouri@example.com', 'sub_ref' => 'SUB-2026-0001', 'res' => null, 'type' => 'subscription', 'amount' => 89.000, 'status' => 'paid', 'verified' => now()->subDays(20)],
            ['ref' => 'PAY-2026-0002', 'email' => 'othman.bennis@example.com', 'sub_ref' => 'SUB-2026-0002', 'res' => null, 'type' => 'subscription', 'amount' => 129.000, 'status' => 'paid', 'verified' => now()->subDays(11)],
            ['ref' => 'PAY-2026-0003', 'email' => 'lina.chafik@example.com', 'sub_ref' => 'SUB-2026-0003', 'res' => null, 'type' => 'subscription', 'amount' => 349.000, 'status' => 'cancelled', 'verified' => now()->subDays(39)],
            ['ref' => 'PAY-2026-0004', 'email' => 'amira.elmansouri@example.com', 'sub_ref' => null, 'res' => $reservations->first(), 'type' => 'reservation', 'amount' => 35.000, 'status' => 'paid', 'verified' => now()->subDays(1)],
            ['ref' => 'PAY-2026-0005', 'email' => 'nadia.rachid@example.com', 'sub_ref' => null, 'res' => $reservations->skip(1)->first(), 'type' => 'reservation', 'amount' => 28.000, 'status' => 'cancelled', 'verified' => now()->subDays(2)],
            ['ref' => 'PAY-2026-0006', 'email' => 'mehdi.amrani@example.com', 'sub_ref' => 'SUB-2026-0008', 'res' => null, 'type' => 'subscription', 'amount' => 1199.000, 'status' => 'initiated', 'verified' => null],
        ];

        foreach ($paymentRows as $row) {
            $member = Member::where('email', $row['email'])->first();
            if (! $member) {
                continue;
            }

            $sub = $row['sub_ref'] ? ($subscriptions[$row['sub_ref']] ?? null) : null;

            Payment::updateOrCreate(
                ['payment_reference' => $row['ref']],
                [
                    'member_id' => $member->id,
                    'reservation_id' => $row['res']?->id,
                    'subscription_id' => $sub?->id,
                    'driver' => 'konnect',
                    'gateway' => 'konnect',
                    'type' => $row['type'],
                    'amount' => $row['amount'],
                    'status' => $row['status'],
                    'gateway_transaction_id' => 'gw-' . $row['ref'],
                    'metadata' => ['source' => 'seed'],
                    'verified_at' => $row['verified'],
                ]
            );
        }

        // --- Loyalty payments (driver = loyalty) ---
        $allMembers = Member::where('status', 'active')->get();
        if ($allMembers->isEmpty()) {
            return;
        }

        $loyaltyRows = [];

        // Paid subscriptions (5)
        foreach ([
            ['amount' => 129.000, 'days' => 30],
            ['amount' => 89.000, 'days' => 25],
            ['amount' => 349.000, 'days' => 18],
            ['amount' => 75.000, 'days' => 10],
            ['amount' => 1199.000, 'days' => 5],
        ] as $r) {
            $loyaltyRows[] = ['type' => 'subscription', 'amount' => $r['amount'], 'status' => 'paid', 'days' => $r['days']];
        }

        // Pending subscriptions (3)
        foreach ([
            ['amount' => 89.000, 'days' => 12],
            ['amount' => 129.000, 'days' => 7],
            ['amount' => 349.000, 'days' => 2],
        ] as $r) {
            $loyaltyRows[] = ['type' => 'subscription', 'amount' => $r['amount'], 'status' => 'pending', 'days' => $r['days']];
        }

        // Failed subscriptions (2)
        foreach ([
            ['amount' => 200.000, 'days' => 20],
            ['amount' => 89.000, 'days' => 8],
        ] as $r) {
            $loyaltyRows[] = ['type' => 'subscription', 'amount' => $r['amount'], 'status' => 'failed', 'days' => $r['days']];
        }

        // Paid reservations (4)
        foreach ([
            ['amount' => 35.000, 'days' => 28],
            ['amount' => 25.000, 'days' => 22],
            ['amount' => 50.000, 'days' => 15],
            ['amount' => 30.000, 'days' => 6],
        ] as $r) {
            $loyaltyRows[] = ['type' => 'reservation', 'amount' => $r['amount'], 'status' => 'paid', 'days' => $r['days']];
        }

        // Pending reservations (3)
        foreach ([
            ['amount' => 15.000, 'days' => 14],
            ['amount' => 45.000, 'days' => 9],
            ['amount' => 25.000, 'days' => 3],
        ] as $r) {
            $loyaltyRows[] = ['type' => 'reservation', 'amount' => $r['amount'], 'status' => 'pending', 'days' => $r['days']];
        }

        // Failed reservations (3)
        foreach ([
            ['amount' => 20.000, 'days' => 35],
            ['amount' => 40.000, 'days' => 16],
            ['amount' => 10.000, 'days' => 4],
        ] as $r) {
            $loyaltyRows[] = ['type' => 'reservation', 'amount' => $r['amount'], 'status' => 'failed', 'days' => $r['days']];
        }

        foreach ($loyaltyRows as $idx => $row) {
            $member = $allMembers->random();
            $createdAt = now()->subDays($row['days']);

            Payment::create([
                'member_id' => $member->id,
                'driver' => 'loyalty',
                'gateway' => 'loyalty_points',
                'type' => $row['type'],
                'amount' => $row['amount'],
                'status' => $row['status'],
                'payment_reference' => 'LOY-' . str_pad((string) (100 + $idx), 5, '0', STR_PAD_LEFT),
                'metadata' => [
                    'source' => 'loyalty_seed',
                    'conversion_rate' => 100,
                    'points_used' => (int) ($row['amount'] * 100),
                ],
                'verified_at' => $row['status'] === 'paid' ? $createdAt : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
