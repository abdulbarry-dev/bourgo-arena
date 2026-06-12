<?php

/** @var TestCase $this */

use App\Models\Activity;
use App\Models\ActivitySession;
use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->member = Member::factory()->create(['status' => 'active']);
    Sanctum::actingAs($this->member, ['*'], 'sanctum');
});

test('list returns paginated activities', function () {
    Activity::factory()->count(15)->create();

    $response = $this->getJson(route('api.v1.activities.index'));

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name'],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
});

test('single activity returns correct shape', function () {
    $activity = Activity::factory()->create();

    $response = $this->getJson(route('api.v1.activities.show', $activity));

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'description',
                'base_price',
            ],
        ]);
});

test('sessions filtered to non-cancelled and within 7-day window', function () {
    $activity = Activity::factory()->create();

    ActivitySession::factory()->create([
        'activity_id' => $activity->id,
        'starts_at_date' => now(),
        'ends_at_date' => now()->addDays(10),
        'is_cancelled' => false,
    ]);

    ActivitySession::factory()->create([
        'activity_id' => $activity->id,
        'starts_at_date' => now(),
        'ends_at_date' => now()->addDays(10),
        'is_cancelled' => false,
    ]);

    ActivitySession::factory()->create([
        'activity_id' => $activity->id,
        'starts_at_date' => now(),
        'ends_at_date' => now()->addDays(10),
        'is_cancelled' => true,
    ]);

    $response = $this->getJson(route('api.v1.activities.slots', $activity));

    $response->assertSuccessful();
    $this->assertCount(2, $response->json('data'));
});

test('sessions exclude already-reserved sessions for a given date', function () {
    $activity = Activity::factory()->create();

    $session = ActivitySession::factory()->create([
        'activity_id' => $activity->id,
        'starts_at_date' => now(),
        'ends_at_date' => now()->addDays(10),
        'is_cancelled' => false,
    ]);

    ActivitySession::factory()->create([
        'activity_id' => $activity->id,
        'starts_at_date' => now(),
        'ends_at_date' => now()->addDays(10),
        'is_cancelled' => false,
    ]);

    $reservationDate = now()->addDay()->toDateString();

    ApiReservation::factory()->create([
        'member_id' => $this->member->id,
        'activity_id' => $activity->id,
        'activity_session_id' => $session->id,
        'date' => $reservationDate,
        'status' => 'confirmed',
    ]);

    $response = $this->getJson(route('api.v1.activities.slots', [
        'activity' => $activity,
        'date' => $reservationDate,
    ]));

    $response->assertSuccessful();
    $this->assertCount(1, $response->json('data'));
});

test('auto-cancels stale reservation when creating new one for same session', function () {
    $member = Member::factory()->active()->create();

    $session = ActivitySession::factory()->create();
    $activity = $session->activity;
    $date = now()->addDay()->toDateString();

    $staleReservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'activity_session_id' => $session->id,
        'date' => $date,
        'status' => 'confirmed',
        'payment_status' => 'pending',
    ]);

    DB::table('api_reservations')->where('id', $staleReservation->id)->update([
        'created_at' => now()->subMinutes(31),
    ]);

    $this->actingAs($member, 'sanctum')->postJson(route('api.v1.reservations.store'), [
        'activity_id' => $activity->id,
        'activity_session_id' => $session->id,
        'date' => $date,
    ]);

    $this->assertDatabaseHas('api_reservations', [
        'id' => $staleReservation->id,
        'status' => 'cancelled',
    ]);

    $newReservation = ApiReservation::where('member_id', $member->id)
        ->where('activity_session_id', $session->id)
        ->whereDate('date', $date)
        ->where('status', '!=', 'cancelled')
        ->first();

    expect($newReservation)->not->toBeNull();
});

test('cancels existing reservation and creates a new one', function () {
    $member = Member::factory()->active()->create();

    $session = ActivitySession::factory()->create();
    $activity = $session->activity;
    $date = now()->addDay()->toDateString();

    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'activity_session_id' => $session->id,
        'date' => $date,
        'status' => 'confirmed',
        'payment_status' => 'pending',
    ]);

    Payment::factory()->create([
        'member_id' => $member->id,
        'reservation_id' => $reservation->id,
        'type' => 'reservation_deposit',
        'status' => 'initiated',
        'updated_at' => now()->subMinutes(5),
    ]);

    $response = $this->actingAs($member, 'sanctum')->postJson(route('api.v1.reservations.store'), [
        'activity_id' => $activity->id,
        'activity_session_id' => $session->id,
        'date' => $date,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('api_reservations', [
        'id' => $reservation->id,
        'status' => 'cancelled',
    ]);
});
