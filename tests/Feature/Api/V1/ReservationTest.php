<?php

use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** @property Member $member */
uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var TestCase $this */
    $this->member = Member::factory()->create(['status' => 'active']);
    Sanctum::actingAs($this->member, ['*'], 'api');
});

test('member can list their reservations', function () {
    ApiReservation::factory()
        ->count(3)
        ->for($this->member)
        ->create();

    $response = $this->getJson(route('api.v1.reservations.index'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'activity_title',
                    'date',
                    'starts_at',
                    'ends_at',
                    'status',
                    'qr_code',
                ],
            ],
            'meta',
        ]);
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

    $response->assertStatus(201)
        ->assertJsonPath('data.activity_title', $activity->title)
        ->assertJsonPath('data.status', 'confirmed');

    $this->assertDatabaseHas('api_reservations', [
        'member_id' => $this->member->id,
        'activity_slot_id' => $slot->id,
        'status' => 'confirmed',
    ]);

    $this->assertEquals(1, $slot->fresh()->booked_count);
    $this->assertNotNull(ApiReservation::first()->qr_code);
});

test('member cannot reserve a full slot', function () {
    $activity = Activity::factory()->create();
    $slot = ActivitySlot::factory()->create([
        'activity_id' => $activity->id,
        'capacity' => 5,
        'booked_count' => 5,
        'is_available' => true,
        'date' => now()->addDay()->toDateString(),
    ]);

    $response = $this->postJson(route('api.v1.reservations.store'), [
        'activity_id' => $activity->id,
        'activity_slot_id' => $slot->id,
        'date' => $slot->date,
        'price' => 10.00,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['activity_slot_id']);
});

test('member can cancel their reservation', function () {
    $reservation = ApiReservation::factory()
        ->for($this->member)
        ->create(['status' => 'confirmed']);

    $slot = $reservation->slot;
    $slot->update(['booked_count' => 1]);

    $response = $this->deleteJson(route('api.v1.reservations.destroy', $reservation));

    $response->assertStatus(200);
    $this->assertEquals('cancelled', $reservation->fresh()->status);
    $this->assertEquals(0, $slot->fresh()->booked_count);
});

test('member cannot cancel someone else reservation', function () {
    $otherMember = Member::factory()->create();
    $reservation = ApiReservation::factory()
        ->for($otherMember)
        ->create();

    $response = $this->deleteJson(route('api.v1.reservations.destroy', $reservation));

    $response->assertStatus(403);
});
