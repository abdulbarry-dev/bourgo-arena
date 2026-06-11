<?php

use App\Models\Activity;
use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('revenue KPI excludes loyalty payment records', function () {
    $member = Member::factory()->create();

    Payment::factory()->create([
        'member_id' => $member->id,
        'driver' => 'konnect',
        'amount' => 100,
        'status' => 'paid',
    ]);

    Payment::factory()->create([
        'member_id' => $member->id,
        'driver' => 'loyalty',
        'gateway' => 'loyalty_points',
        'amount' => 50,
        'status' => 'paid',
    ]);

    $kpi = app(AnalyticsService::class)->getKpiData();

    expect($kpi['revenue_mtd'])->toBe((float) 100);
});

test('revenue KPI excludes loyalty-paid subscriptions', function () {
    $plan = Plan::factory()->create(['price' => 200, 'duration_days' => 30]);

    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'amount_paid' => 200,
        'payment_method' => 'konnect',
        'status' => 'active',
    ]);

    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'amount_paid' => 200,
        'payment_method' => 'loyalty_points',
        'status' => 'active',
    ]);

    $kpi = app(AnalyticsService::class)->getKpiData();

    expect($kpi['revenue_mtd'])->toBe((float) 200);
});

test('revenue KPI excludes loyalty-paid reservations from the ApiReservation sum', function () {
    $activity = Activity::factory()->create(['base_price' => 30]);

    $regularReservation = ApiReservation::factory()->forActivity($activity)->create([
        'price' => 30,
        'status' => 'confirmed',
        'payment_status' => 'paid',
    ]);

    $loyaltyReservation = ApiReservation::factory()->forActivity($activity)->create([
        'price' => 30,
        'status' => 'confirmed',
        'payment_status' => 'paid',
    ]);

    Payment::factory()->create([
        'reservation_id' => $loyaltyReservation->id,
        'member_id' => $loyaltyReservation->member_id,
        'driver' => 'loyalty',
        'gateway' => 'loyalty_points',
        'amount' => 30,
        'status' => 'paid',
    ]);

    $kpi = app(AnalyticsService::class)->getKpiData();

    // getKpiData sums Payment + Subscription + ApiReservation
    // Payment sum: 0 (loyalty payment excluded by driver != 'loyalty')
    // Subscription sum: 0
    // ApiReservation sum: 30 (regular reservation included, loyalty excluded by whereDoesntHave)
    expect($kpi['revenue_mtd'])->toBe((float) 30);
});

test('revenue by method fallback excludes loyalty gateway', function () {
    Payment::factory()->create([
        'driver' => 'konnect',
        'gateway' => 'konnect',
        'amount' => 75,
        'status' => 'completed',
    ]);

    Payment::factory()->create([
        'driver' => 'loyalty',
        'gateway' => 'loyalty_points',
        'amount' => 25,
        'status' => 'completed',
    ]);

    $result = app(AnalyticsService::class)->getRevenueByMethod(days: 30);

    expect($result['labels'])->not->toContain('loyalty_points');
    expect($result['labels'])->toContain('konnect');

    $konnectIndex = array_search('konnect', $result['labels']);
    expect((float) $result['values'][$konnectIndex])->toBe(75.0);
});

test('revenue KPI excludes all loyalty-related records from the combined sum', function () {
    $plan = Plan::factory()->create(['price' => 150, 'duration_days' => 30]);
    $activity = Activity::factory()->create(['base_price' => 20]);

    $member = Member::factory()->create();

    Payment::factory()->create([
        'member_id' => $member->id,
        'driver' => 'konnect',
        'amount' => 100,
        'status' => 'paid',
    ]);

    Payment::factory()->create([
        'member_id' => $member->id,
        'driver' => 'loyalty',
        'gateway' => 'loyalty_points',
        'amount' => 60,
        'status' => 'paid',
    ]);

    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'amount_paid' => 150,
        'payment_method' => 'konnect',
        'status' => 'active',
    ]);

    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'amount_paid' => 150,
        'payment_method' => 'loyalty_points',
        'status' => 'active',
    ]);

    $regularReservation = ApiReservation::factory()->forActivity($activity)->create([
        'price' => 20,
        'status' => 'confirmed',
        'payment_status' => 'paid',
    ]);

    $loyaltyReservation = ApiReservation::factory()->forActivity($activity)->create([
        'price' => 20,
        'status' => 'confirmed',
        'payment_status' => 'paid',
    ]);

    Payment::factory()->create([
        'reservation_id' => $loyaltyReservation->id,
        'member_id' => $loyaltyReservation->member_id,
        'driver' => 'loyalty',
        'gateway' => 'loyalty_points',
        'amount' => 20,
        'status' => 'paid',
    ]);

    $kpi = app(AnalyticsService::class)->getKpiData();

    // getKpiData sums Payment + Subscription + ApiReservation
    // Payment sum: 100 (konnect only; loyalty excluded)
    // Subscription sum: 150 (konnect only; loyalty_points excluded)
    // ApiReservation sum: 20 (regular reservation; loyalty excluded by whereDoesntHave)
    // Total: 100 + 150 + 20 = 270
    expect($kpi['revenue_mtd'])->toBe((float) 270);
});
