<?php

/** @var TestCase $this */

use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\Subscription;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);
    Sanctum::actingAs($this->member, ['*'], 'sanctum');
});

test('member can create an activity reservation', function () {
    $activity = Activity::factory()->create(['base_price' => 100.00, 'category' => 'Padel']);
    $slot = ActivitySlot::factory()->create([
        'activity_id' => $activity->id,
        'capacity' => 10,
        'booked_count' => 0,
        'is_available' => true,
        'date' => now()->addDay()->toDateString(),
    ]);

    $response = $this->postJson(route('api.v1.reservations.store'), [
        'activity_id' => $activity->id,
        'activity_slot_id' => $slot->id,
        'date' => $slot->date,
        'price' => 0.01,
    ]);

    $response->assertStatus(201);

    $reservation = ApiReservation::query()->latest('id')->first();
    expect($reservation)->not->toBeNull();
    expect((float) $reservation->price)->toBe(100.00);

    // The task says "decrements slot booked_count", but logically it increments booked_count.
    // If the intention was "decrements availability", then incrementing booked_count is correct.
    $this->assertEquals(1, $slot->fresh()->booked_count);

    expect(LoyaltyPoint::query()->where('member_id', $this->member->id)->count())->toBe(1);
    expect($this->member->fresh()->loyalty_points)->toBe(10);
});

test('member cannot book a slot that does not belong to the given activity', function () {
    $activity = Activity::factory()->create();
    $otherActivity = Activity::factory()->create();

    $slot = ActivitySlot::factory()->create([
        'activity_id' => $otherActivity->id,
        'date' => now()->addDay()->toDateString(),
    ]);

    $response = $this->postJson(route('api.v1.reservations.store'), [
        'activity_id' => $activity->id,
        'activity_slot_id' => $slot->id,
        'date' => $slot->date,
    ]);

    $response->assertStatus(422);
});

test('reservation price is recalculated server-side (price shield) regardless of tier', function () {
    Subscription::factory()->count(4)->create([
        'member_id' => $this->member->id,
        'status' => 'active',
        'ends_at' => now()->addDays(10)->toDateString(),
    ]);

    $activity = Activity::factory()->create(['base_price' => 100.00, 'category' => 'Padel']);
    $slot = ActivitySlot::factory()->create([
        'activity_id' => $activity->id,
        'capacity' => 10,
        'booked_count' => 0,
        'is_available' => true,
        'date' => now()->addDay()->toDateString(),
    ]);

    $response = $this->postJson(route('api.v1.reservations.store'), [
        'activity_id' => $activity->id,
        'activity_slot_id' => $slot->id,
        'date' => $slot->date,
        'price' => 0.01,
    ]);

    $response->assertStatus(201);

    $reservation = ApiReservation::query()->latest('id')->first();
    expect($reservation)->not->toBeNull();
    expect((float) $reservation->price)->toBe(100.00);
});

test('member can cancel their reservation', function () {
    /** @var TestCase $this */
    $reservation = ApiReservation::factory()
        ->for($this->member)
        ->create(['status' => 'confirmed']);

    $slot = $reservation->slot;
    $slot->update(['booked_count' => 1]);

    $response = $this->deleteJson(route('api.v1.reservations.destroy', $reservation));

    $response->assertSuccessful();
    $this->assertEquals('cancelled', $reservation->fresh()->status);

    // Cancelling returns slot availability (decrements booked_count)
    $this->assertEquals(0, $slot->fresh()->booked_count);
});

test('member cannot cancel someone else reservation', function () {
    $otherMember = Member::factory()->create();
    $reservation = ApiReservation::factory()
        ->for($otherMember)
        ->create();

    $response = $this->deleteJson(route('api.v1.reservations.destroy', $reservation));

    $response->assertForbidden();
});
