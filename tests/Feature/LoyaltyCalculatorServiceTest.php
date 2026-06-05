<?php

/** @var TestCase $this */

use App\Models\Activity;
use App\Models\ApiReservation;
use App\Models\LoyaltyAuditLog;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\Subscription;
use App\Services\LoyaltyCalculatorService;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

beforeEach(function () {
    Notification::fake();
});

test('loyalty reservation accrual is idempotent', function () {
    /** @var Member $member */
    $member = Member::factory()->active()->create(['loyalty_points' => 0]);
    $activity = Activity::factory()->create(['category' => 'Padel']);

    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'paid',
        'status' => 'confirmed',
        'date' => now()->toDateString(),
    ]);

    $service = app(LoyaltyCalculatorService::class);

    expect($service->creditVariableForReservation($reservation))->toBeTrue();
    expect($service->creditVariableForReservation($reservation))->toBeFalse();

    expect(LoyaltyPoint::query()->where('member_id', $member->id)->count())->toBe(1);
    expect($member->fresh()->loyalty_points)->toBe(10);
});

test('variable loyalty is not credited for unpaid or cancelled reservations', function () {
    $member = Member::factory()->active()->create(['loyalty_points' => 0]);
    $activity = Activity::factory()->create(['category' => 'Padel']);

    $unpaid = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'pending',
        'status' => 'confirmed',
        'date' => now()->toDateString(),
    ]);

    $cancelled = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'paid',
        'status' => 'cancelled',
        'date' => now()->toDateString(),
    ]);

    $service = app(LoyaltyCalculatorService::class);

    expect($service->creditVariableForReservation($unpaid))->toBeFalse();
    expect($service->creditVariableForReservation($cancelled))->toBeFalse();

    expect(LoyaltyPoint::query()->where('member_id', $member->id)->count())->toBe(0);
    expect($member->fresh()->loyalty_points)->toBe(0);
});

test('variable loyalty is not credited for non-eligible categories', function () {
    $member = Member::factory()->active()->create(['loyalty_points' => 0]);
    $activity = Activity::factory()->create(['category' => 'Tennis']);

    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'paid',
        'status' => 'confirmed',
        'date' => now()->toDateString(),
    ]);

    $service = app(LoyaltyCalculatorService::class);

    expect($service->creditVariableForReservation($reservation))->toBeFalse();
    expect(LoyaltyPoint::query()->where('member_id', $member->id)->count())->toBe(0);
});

test('fixed monthly renewal accrual is idempotent and writes audit log', function () {
    $member = Member::factory()->active()->create(['loyalty_points' => 0]);
    $subscription = Subscription::factory()->create([
        'member_id' => $member->id,
        'status' => 'active',
        'ends_at' => now()->addDays(10)->toDateString(),
    ]);

    $service = app(LoyaltyCalculatorService::class);

    expect($service->creditFixedMonthlyRenewal($subscription))->toBeTrue();
    expect($service->creditFixedMonthlyRenewal($subscription))->toBeFalse();

    expect(LoyaltyPoint::query()->where('member_id', $member->id)->where('transaction_type', 'fixed')->count())->toBe(1);
    expect($member->fresh()->loyalty_points)->toBe(250);

    $audit = LoyaltyAuditLog::query()->where('member_id', $member->id)->first();
    expect($audit)->not->toBeNull();
    expect($audit->balance_before)->toBe(0);
    expect($audit->balance_after)->toBe(250);
});

test('variable loyalty uses frequency logic (second reservation in month yields 20 points at standard tier)', function () {
    $member = Member::factory()->active()->create(['loyalty_points' => 0]);
    $activity = Activity::factory()->create(['category' => 'Padel']);

    $first = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'paid',
        'status' => 'confirmed',
        'date' => now()->startOfMonth()->addDays(2)->toDateString(),
    ]);

    $service = app(LoyaltyCalculatorService::class);

    expect($service->creditVariableForReservation($first))->toBeTrue();
    expect($member->fresh()->loyalty_points)->toBe(10);

    $second = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'paid',
        'status' => 'confirmed',
        'date' => now()->startOfMonth()->addDays(3)->toDateString(),
    ]);

    expect($service->creditVariableForReservation($second))->toBeTrue();
    expect($member->fresh()->loyalty_points)->toBe(30);
});
