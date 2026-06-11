<?php

namespace Database\Seeders\Staging;

use App\Models\Activity;
use App\Models\ApiReservation;
use App\Models\LoyaltyAuditLog;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BulkSubscriptionsPaymentsSeeder extends Seeder
{
    private array $ips = [
        '196.179.1.10', '41.225.120.33', '197.27.84.12', '154.120.66.9',
        '41.229.50.1', '196.203.4.15', '197.1.143.22', '41.231.80.55',
        '197.0.0.100', '41.228.99.44', '196.184.0.33', '41.232.15.66',
    ];

    private array $userAgents = [
        'Bourgo Arena/2.0 (Android 14; SM-G991B)',
        'Bourgo Arena/2.0 (iOS 17.4; iPhone 15 Pro)',
        'Bourgo Arena/1.9 (Android 13; Pixel 7)',
        'Bourgo Arena/2.0 (iOS 16.6; iPhone 13)',
        'okhttp/4.12.0',
        'Dart/3.3 (dart:io)',
    ];

    public function run(): void
    {
        // If we have varied statuses already, the full seeder has run — just ensure diversity.
        $hasVariedStatuses = Subscription::whereIn('status', ['expired', 'suspended'])->count() >= 50;

        if (Subscription::count() > 200 && $hasVariedStatuses) {
            $this->command?->info('  Subscriptions already seeded. Skipping.');

            return;
        }

        // Subscriptions exist but are all-active (created by Member::factory).
        // Diversify their statuses rather than creating duplicates.
        if (Subscription::count() > 200 && ! $hasVariedStatuses) {
            $this->diversifySubscriptionStatuses();

            return;
        }

        $plans = Plan::withoutGlobalScopes()->where('is_archived', false)->get();
        $manager = User::where('role', UserRole::Manager)->first();
        $activeMembers = Member::where('status', 'active')->where('state', 'active')->get();

        if ($plans->isEmpty() || $activeMembers->isEmpty()) {
            $this->command?->warn('  No plans or active members found. Skipping.');

            return;
        }

        $this->command?->info("  Seeding subscriptions + payments for {$activeMembers->count()} active members...");

        $loyaltyBalance = [];

        foreach ($activeMembers as $member) {
            $memberLoyalty = (int) $member->loyalty_points;
            $roll = rand(1, 100);

            if ($roll <= 65) {
                [$memberLoyalty] = $this->createActiveSubscription($member, $plans, $manager, $memberLoyalty);
            } elseif ($roll <= 80) {
                [$memberLoyalty] = $this->createExpiredSubscription($member, $plans, $manager, $memberLoyalty);

                if (rand(0, 1)) {
                    [$memberLoyalty] = $this->createActiveSubscription($member, $plans, $manager, $memberLoyalty);
                }
            } elseif ($roll <= 92) {
                [$memberLoyalty] = $this->createSuspendedSubscription($member, $plans, $manager, $memberLoyalty);
            } else {
                [$memberLoyalty] = $this->createPendingSubscription($member, $plans, $manager, $memberLoyalty);
            }

            if (rand(0, 2) === 0) {
                [$memberLoyalty] = $this->createExpiredSubscription($member, $plans, $manager, $memberLoyalty);
            }

            $loyaltyBalance[$member->id] = max(0, $memberLoyalty);
        }

        $this->seedActivityReservations($activeMembers, $loyaltyBalance);
        $this->applyLoyaltyBalances($loyaltyBalance);

        $this->command?->info(sprintf(
            '  Subscriptions: %d | Payments: %d | LoyaltyPoints entries: %d | Reservations: %d',
            Subscription::count(), Payment::count(), LoyaltyPoint::count(), ApiReservation::count()
        ));
    }

    private function createActiveSubscription(Member $member, $plans, ?User $manager, int $loyaltyBalance): array
    {
        $plan = $plans->random();
        $startDaysAgo = rand(5, $plan->duration_days - 3);
        $startDate = now()->subDays($startDaysAgo);

        $subscription = Subscription::create([
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => $startDate->toDateString(),
            'ends_at' => $startDate->copy()->addDays($plan->duration_days)->toDateString(),
            'payment_method' => $this->randomPaymentMethod(),
            'amount_paid' => $plan->price,
            'enrolled_by' => $manager?->id,
            'created_at' => $startDate,
        ]);

        $loyaltyBalance = $this->createSubscriptionPayment($member, $subscription, $plan, $startDate, $loyaltyBalance);
        $loyaltyBalance = $this->maybeCreditRenewalPoints($member, $subscription, $loyaltyBalance);

        return [$loyaltyBalance];
    }

    private function createExpiredSubscription(Member $member, $plans, ?User $manager, int $loyaltyBalance): array
    {
        $plan = $plans->random();
        $endDaysAgo = rand(5, 60);
        $startDate = now()->subDays($plan->duration_days + $endDaysAgo);

        $subscription = Subscription::create([
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'status' => 'expired',
            'starts_at' => $startDate->toDateString(),
            'ends_at' => now()->subDays($endDaysAgo)->toDateString(),
            'payment_method' => $this->randomPaymentMethod(),
            'amount_paid' => $plan->price,
            'enrolled_by' => $manager?->id,
            'created_at' => $startDate,
        ]);

        $loyaltyBalance = $this->createSubscriptionPayment($member, $subscription, $plan, $startDate, $loyaltyBalance);
        $loyaltyBalance = $this->maybeCreditRenewalPoints($member, $subscription, $loyaltyBalance);

        return [$loyaltyBalance];
    }

    private function createSuspendedSubscription(Member $member, $plans, ?User $manager, int $loyaltyBalance): array
    {
        $plan = $plans->random();
        $startDaysAgo = rand(15, 90);
        $startDate = now()->subDays($startDaysAgo);

        $subscription = Subscription::create([
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'status' => 'suspended',
            'starts_at' => $startDate->toDateString(),
            'ends_at' => $startDate->copy()->addDays($plan->duration_days)->toDateString(),
            'suspended_at' => now()->subDays(rand(1, $startDaysAgo - 1)),
            'days_remaining' => rand(5, $plan->duration_days - 5),
            'payment_method' => $this->randomPaymentMethod(),
            'amount_paid' => $plan->price,
            'enrolled_by' => $manager?->id,
            'created_at' => $startDate,
        ]);

        $loyaltyBalance = $this->createSubscriptionPayment($member, $subscription, $plan, $startDate, $loyaltyBalance);

        return [$loyaltyBalance];
    }

    private function createPendingSubscription(Member $member, $plans, ?User $manager, int $loyaltyBalance): array
    {
        $plan = $plans->random();

        Subscription::create([
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays($plan->duration_days)->toDateString(),
            'payment_method' => 'konnect',
            'amount_paid' => $plan->price,
            'enrolled_by' => $manager?->id,
            'created_at' => now()->subMinutes(rand(5, 120)),
        ]);

        return [$loyaltyBalance];
    }

    private function createSubscriptionPayment(Member $member, Subscription $subscription, $plan, $paymentDate, int $loyaltyBalance): int
    {
        $driver = $this->randomDriver();
        $reference = 'pay_'.Str::random(28);

        if ($driver === 'loyalty') {
            $pointsNeeded = (int) ceil((float) $plan->price * 100);
            if ($loyaltyBalance < $pointsNeeded) {
                $driver = 'konnect';
            }
        }

        if ($driver === 'loyalty') {
            $pointsNeeded = (int) ceil((float) $plan->price * 100);
            $balanceBefore = $loyaltyBalance;
            $balanceAfter = $loyaltyBalance - $pointsNeeded;
            $idempotencyKey = 'seed-loyalty-sub-'.$subscription->id.'-'.$member->id;

            Payment::create([
                'member_id' => $member->id,
                'subscription_id' => $subscription->id,
                'driver' => 'loyalty',
                'gateway' => 'loyalty_points',
                'type' => 'subscription',
                'amount' => $plan->price,
                'status' => 'paid',
                'payment_reference' => 'loyalty_'.Str::random(32),
                'verified_at' => $paymentDate,
                'ip_address' => $this->ips[array_rand($this->ips)],
                'country_code' => 'TN',
                'city' => $this->randomCity(),
                'created_at' => $paymentDate,
            ]);

            LoyaltyPoint::updateOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'member_id' => $member->id,
                    'points' => -$pointsNeeded,
                    'transaction_type' => 'payment',
                    'source_type' => 'App\\Models\\Subscription',
                    'source_id' => $subscription->id,
                    'created_at' => $paymentDate,
                ]
            );

            LoyaltyAuditLog::create([
                'member_id' => $member->id,
                'action' => 'payment',
                'points_changed' => -$pointsNeeded,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source_type' => 'App\\Models\\Subscription',
                'source_id' => $subscription->id,
                'ip_address' => $this->ips[array_rand($this->ips)],
                'user_agent' => $this->userAgents[array_rand($this->userAgents)],
                'metadata' => [
                    'amount_tnd' => (float) $plan->price,
                    'points_used' => $pointsNeeded,
                    'conversion_rate' => 100,
                    'plan_name' => $plan->name,
                ],
                'created_at' => $paymentDate,
            ]);

            return $balanceAfter;
        }

        Payment::create([
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'driver' => $driver,
            'gateway' => $driver === 'konnect' ? 'konnect' : null,
            'type' => 'subscription',
            'amount' => $plan->price,
            'status' => 'paid',
            'payment_reference' => $reference,
            'gateway_transaction_id' => $driver === 'konnect' ? 'gw-'.Str::random(16) : null,
            'verified_at' => $paymentDate,
            'ip_address' => $this->ips[array_rand($this->ips)],
            'country_code' => 'TN',
            'city' => $this->randomCity(),
            'created_at' => $paymentDate,
        ]);

        if (rand(0, 9) === 0) {
            Payment::create([
                'member_id' => $member->id,
                'subscription_id' => $subscription->id,
                'driver' => 'konnect',
                'gateway' => 'konnect',
                'type' => 'subscription',
                'amount' => $plan->price,
                'status' => 'failed',
                'payment_reference' => 'pay_'.Str::random(28),
                'created_at' => $paymentDate->copy()->subMinutes(rand(5, 30)),
            ]);
        }

        return $loyaltyBalance;
    }

    private function maybeCreditRenewalPoints(Member $member, Subscription $subscription, int $loyaltyBalance): int
    {
        if (rand(0, 1) === 0) {
            return $loyaltyBalance;
        }

        $points = 250;
        $idempotencyKey = 'seed-renewal-credit-'.$subscription->id;
        $paymentDate = $subscription->created_at ?? now();

        LoyaltyPoint::updateOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'member_id' => $member->id,
                'points' => $points,
                'transaction_type' => 'fixed',
                'source_type' => 'App\\Models\\Subscription',
                'source_id' => $subscription->id,
                'created_at' => $paymentDate,
            ]
        );

        LoyaltyAuditLog::create([
            'member_id' => $member->id,
            'action' => 'earn',
            'points_changed' => $points,
            'balance_before' => $loyaltyBalance,
            'balance_after' => $loyaltyBalance + $points,
            'source_type' => 'App\\Models\\Subscription',
            'source_id' => $subscription->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Laravel/Scheduler',
            'metadata' => ['reason' => 'monthly_renewal_credit', 'plan_id' => $subscription->plan_id],
            'created_at' => $paymentDate,
        ]);

        return $loyaltyBalance + $points;
    }

    private function seedActivityReservations($members, array &$loyaltyBalance): void
    {
        $activities = Activity::where('is_active', true)->get();

        if ($activities->isEmpty()) {
            return;
        }

        $reservationMembers = $members->random(min(200, $members->count()));

        foreach ($reservationMembers as $member) {
            $resCount = rand(1, 5);

            for ($r = 0; $r < $resCount; $r++) {
                $activity = $activities->random();
                $daysOffset = rand(-30, 14);
                $date = now()->addDays($daysOffset)->toDateString();
                $isPast = $daysOffset < 0;
                $isCancelled = $isPast && rand(0, 9) < 2;

                $reservation = ApiReservation::create([
                    'member_id' => $member->id,
                    'activity_id' => $activity->id,
                    'date' => $date,
                    'price' => $activity->base_price,
                    'status' => $isCancelled ? 'cancelled' : 'confirmed',
                    'payment_status' => $isCancelled ? 'refunded' : ($isPast ? 'paid' : 'pending'),
                    'cancelled_at' => $isCancelled ? now()->subDays(abs($daysOffset) - 1) : null,
                    'created_at' => now()->addDays($daysOffset)->subDays(rand(1, 3)),
                ]);

                if (! $isCancelled && $isPast) {
                    $this->createReservationPayment($member, $reservation, $activity, $loyaltyBalance);
                }
            }
        }
    }

    private function createReservationPayment(Member $member, ApiReservation $reservation, Activity $activity, array &$loyaltyBalance): void
    {
        $driver = rand(0, 9) < 2 ? 'loyalty' : (rand(0, 1) ? 'konnect' : 'cash');
        $memberId = $member->id;
        $amount = (float) $activity->base_price;
        $paymentDate = now()->subDays(rand(1, 30));

        if ($driver === 'loyalty') {
            $pointsNeeded = (int) ceil($amount * 100);
            $balance = $loyaltyBalance[$memberId] ?? (int) $member->loyalty_points;

            if ($balance < $pointsNeeded) {
                $driver = 'konnect';
            } else {
                $balanceBefore = $balance;
                $balanceAfter = $balance - $pointsNeeded;
                $idempotencyKey = 'seed-loyalty-res-'.$reservation->id.'-'.$memberId;

                Payment::create([
                    'member_id' => $memberId,
                    'reservation_id' => $reservation->id,
                    'driver' => 'loyalty',
                    'gateway' => 'loyalty_points',
                    'type' => 'reservation',
                    'amount' => $amount,
                    'status' => 'paid',
                    'payment_reference' => 'loyalty_'.Str::random(32),
                    'verified_at' => $paymentDate,
                    'ip_address' => $this->ips[array_rand($this->ips)],
                    'country_code' => 'TN',
                    'created_at' => $paymentDate,
                ]);

                LoyaltyPoint::updateOrCreate(
                    ['idempotency_key' => $idempotencyKey],
                    [
                        'member_id' => $memberId,
                        'points' => -$pointsNeeded,
                        'transaction_type' => 'payment',
                        'source_type' => 'App\\Models\\ApiReservation',
                        'source_id' => $reservation->id,
                        'created_at' => $paymentDate,
                    ]
                );

                LoyaltyAuditLog::create([
                    'member_id' => $memberId,
                    'action' => 'payment',
                    'points_changed' => -$pointsNeeded,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'source_type' => 'App\\Models\\ApiReservation',
                    'source_id' => $reservation->id,
                    'ip_address' => $this->ips[array_rand($this->ips)],
                    'user_agent' => $this->userAgents[array_rand($this->userAgents)],
                    'metadata' => [
                        'amount_tnd' => $amount,
                        'points_used' => $pointsNeeded,
                        'conversion_rate' => 100,
                        'activity_title' => $activity->title,
                    ],
                    'created_at' => $paymentDate,
                ]);

                $loyaltyBalance[$memberId] = $balanceAfter;

                if (rand(0, 3) === 0) {
                    $earnPoints = rand(10, 50);
                    $idempotencyEarn = 'seed-earn-res-'.$reservation->id.'-'.$memberId;

                    LoyaltyPoint::updateOrCreate(
                        ['idempotency_key' => $idempotencyEarn],
                        [
                            'member_id' => $memberId,
                            'points' => $earnPoints,
                            'transaction_type' => 'variable',
                            'source_type' => 'App\\Models\\ApiReservation',
                            'source_id' => $reservation->id,
                            'created_at' => $paymentDate->copy()->addMinutes(1),
                        ]
                    );

                    $loyaltyBalance[$memberId] = $balanceAfter + $earnPoints;
                }

                return;
            }
        }

        Payment::create([
            'member_id' => $memberId,
            'reservation_id' => $reservation->id,
            'driver' => $driver,
            'gateway' => $driver === 'konnect' ? 'konnect' : null,
            'type' => 'reservation',
            'amount' => $amount,
            'status' => 'paid',
            'payment_reference' => 'pay_'.Str::random(28),
            'gateway_transaction_id' => $driver === 'konnect' ? 'gw-'.Str::random(16) : null,
            'verified_at' => $paymentDate,
            'ip_address' => $this->ips[array_rand($this->ips)],
            'country_code' => 'TN',
            'city' => $this->randomCity(),
            'created_at' => $paymentDate,
        ]);
    }

    private function applyLoyaltyBalances(array $loyaltyBalance): void
    {
        foreach ($loyaltyBalance as $memberId => $balance) {
            Member::where('id', $memberId)->update(['loyalty_points' => max(0, $balance)]);
        }
    }

    private function randomPaymentMethod(): string
    {
        return ['konnect', 'konnect', 'konnect', 'cash', 'cash'][rand(0, 4)];
    }

    private function randomDriver(): string
    {
        $roll = rand(1, 100);

        return match (true) {
            $roll <= 55 => 'konnect',
            $roll <= 80 => 'cash',
            $roll <= 92 => 'loyalty',
            default => 'konnect',
        };
    }

    private function randomCity(): string
    {
        return ['Tunis', 'Sfax', 'Sousse', 'Monastir', 'Bizerte', 'Nabeul', 'Hammamet', 'La Marsa'][rand(0, 7)];
    }

    /**
     * Update a portion of all-active subscriptions to expired/suspended/pending so
     * the Subscription Health chart shows a realistic multi-status distribution.
     *
     * Target mix: ~58% active, ~25% expired, ~12% suspended, ~5% pending.
     */
    private function diversifySubscriptionStatuses(): void
    {
        $this->command?->info('  Diversifying subscription statuses...');

        $ids = Subscription::where('status', 'active')->pluck('id')->shuffle();
        $total = $ids->count();

        $expiredCount = (int) round($total * 0.25);
        $suspendedCount = (int) round($total * 0.12);
        $pendingCount = (int) round($total * 0.05);

        // Mark as expired: push ends_at into the past
        $ids->slice(0, $expiredCount)->each(function (int $id): void {
            $endDaysAgo = rand(5, 90);
            Subscription::where('id', $id)->update([
                'status' => 'expired',
                'ends_at' => now()->subDays($endDaysAgo)->toDateString(),
            ]);
        });

        // Mark as suspended: keep ends_at future, set suspended_at + days_remaining
        $ids->slice($expiredCount, $suspendedCount)->each(function (int $id): void {
            Subscription::where('id', $id)->update([
                'status' => 'suspended',
                'suspended_at' => now()->subDays(rand(1, 30)),
                'days_remaining' => rand(5, 25),
            ]);
        });

        // Mark as pending
        $ids->slice($expiredCount + $suspendedCount, $pendingCount)->each(function (int $id): void {
            Subscription::where('id', $id)->update(['status' => 'pending']);
        });

        $this->command?->info(sprintf(
            '  Diversified: %d expired, %d suspended, %d pending',
            $expiredCount, $suspendedCount, $pendingCount
        ));
    }
}
