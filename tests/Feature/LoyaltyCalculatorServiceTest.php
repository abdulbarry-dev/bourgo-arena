<?php

use App\DTOs\Tier\TierData;
use App\DTOs\Tier\TierResolution;
use App\Models\Activity;
use App\Models\ApiReservation;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\Service;
use App\Models\Subscription;
use App\Services\LoyaltyCalculatorService;
use App\Services\TierResolutionService;
use Illuminate\Support\Facades\Config;

test('loyalty calculator service uses clean source type for subscription', function () {
    $member = Member::factory()->create(['loyalty_points' => 0]);
    $subscription = Subscription::factory()->create(['member_id' => $member->id]);

    $tierData = new TierData(
        label: 'Bronze',
        multiplier: 1.0,
        requiredSubscriptions: 0
    );
    $tierResolution = new TierResolution(
        currentTier: $tierData,
        currentSubscriptionCount: 0
    );

    $tierMock = mock(TierResolutionService::class);
    $tierMock->shouldReceive('resolveTier')->andReturn($tierResolution);

    Config::set('loyalty.fixed_monthly_renewal_points', 100);

    $service = new LoyaltyCalculatorService($tierMock);
    $service->creditFixedMonthlyRenewal($subscription);

    $point = LoyaltyPoint::where('member_id', $member->id)->first();
    expect($point->source_type)->toBe('subscription');
});

test('loyalty calculator service uses clean source type for reservation', function () {
    $member = Member::factory()->create(['loyalty_points' => 0]);
    $serviceObj = Service::factory()->create(['name' => 'Padel']);
    $activity = Activity::factory()->create(['service_id' => $serviceObj->id]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'payment_status' => 'paid',
        'status' => 'confirmed',
        'date' => now()->toDateString(),
    ]);

    $tierData = new TierData(
        label: 'Bronze',
        multiplier: 1.0,
        requiredSubscriptions: 0
    );
    $tierResolution = new TierResolution(
        currentTier: $tierData,
        currentSubscriptionCount: 0
    );

    $tierMock = mock(TierResolutionService::class);
    $tierMock->shouldReceive('resolveTier')->andReturn($tierResolution);

    Config::set('loyalty.variable.eligible_categories', [$activity->service?->name]);
    Config::set('loyalty.variable.base_points_per_reservation', 50);

    $service = new LoyaltyCalculatorService($tierMock);
    $service->creditVariableForReservation($reservation);

    $point = LoyaltyPoint::where('member_id', $member->id)->first();
    expect($point->source_type)->toBe('reservation');
});
