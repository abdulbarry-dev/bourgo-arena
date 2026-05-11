<?php

/** @var \Tests\TestCase $this */

use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->member = Member::factory()->create(['status' => 'active']);
    Sanctum::actingAs($this->member, ['*'], 'api');
});

test('member can create an activity reservation', function () {
    $activity = Activity::factory()->create(['base_price' => 100.00]);
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
        'price' => 100.00,
    ]);

    $response->assertStatus(201);

    // The task says "decrements slot booked_count", but logically it increments booked_count.
    // If the intention was "decrements availability", then incrementing booked_count is correct.
    $this->assertEquals(1, $slot->fresh()->booked_count);
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
