<?php

/** @var TestCase $this */

use App\Models\Activity;
use App\Models\ActivitySession;
use App\Models\ApiReservation;
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
    $activity = Activity::factory()->create(['base_price' => 100.00]);
    $session = ActivitySession::factory()->create([
        'activity_id' => $activity->id,
        'starts_at_date' => now(),
        'ends_at_date' => now()->addMonth(),
    ]);

    $reservationDate = now()->addDay()->toDateString();

    $response = $this->postJson(route('api.v1.reservations.store'), [
        'activity_id' => $activity->id,
        'activity_session_id' => $session->id,
        'date' => $reservationDate,
        'price' => 0.01,
    ]);

    $response->assertStatus(201);

    $reservation = ApiReservation::query()->latest('id')->first();
    expect($reservation)->not->toBeNull();
    expect((float) $reservation->price)->toBe(100.00);
    expect($reservation->activity_session_id)->toBe($session->id);
    expect($reservation->date->toDateString())->toBe($reservationDate);
    expect($reservation->status)->toBe('pending');
});

test('member cannot book a session already reserved for the same date', function () {
    $activity = Activity::factory()->create();
    $session = ActivitySession::factory()->create([
        'activity_id' => $activity->id,
        'starts_at_date' => now(),
    ]);

    $reservationDate = now()->addDay()->toDateString();

    ApiReservation::factory()->create([
        'member_id' => Member::factory()->create()->id,
        'activity_id' => $activity->id,
        'activity_session_id' => $session->id,
        'date' => $reservationDate,
        'status' => 'confirmed',
    ]);

    $response = $this->postJson(route('api.v1.reservations.store'), [
        'activity_id' => $activity->id,
        'activity_session_id' => $session->id,
        'date' => $reservationDate,
    ]);

    $response->assertStatus(422);
});

test('member cannot book a session that does not belong to the given activity', function () {
    $activity = Activity::factory()->create();
    $otherActivity = Activity::factory()->create();

    $session = ActivitySession::factory()->create([
        'activity_id' => $otherActivity->id,
    ]);

    $reservationDate = now()->addDay()->toDateString();

    $response = $this->postJson(route('api.v1.reservations.store'), [
        'activity_id' => $activity->id,
        'activity_session_id' => $session->id,
        'date' => $reservationDate,
    ]);

    $response->assertStatus(422);
});

test('reservation price is recalculated server-side (price shield) regardless of tier', function () {
    Subscription::factory()->count(4)->create([
        'member_id' => $this->member->id,
        'status' => 'active',
        'ends_at' => now()->addDays(10)->toDateString(),
    ]);

    $activity = Activity::factory()->create(['base_price' => 100.00]);
    $session = ActivitySession::factory()->create([
        'activity_id' => $activity->id,
        'starts_at_date' => now(),
    ]);

    $reservationDate = now()->addDay()->toDateString();

    $response = $this->postJson(route('api.v1.reservations.store'), [
        'activity_id' => $activity->id,
        'activity_session_id' => $session->id,
        'date' => $reservationDate,
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

    $response = $this->deleteJson(route('api.v1.reservations.destroy', $reservation));

    $response->assertSuccessful();
    $this->assertEquals('cancelled', $reservation->fresh()->status);
});

test('member cannot cancel someone else reservation', function () {
    $otherMember = Member::factory()->create();
    $reservation = ApiReservation::factory()
        ->for($otherMember)
        ->create();

    $response = $this->deleteJson(route('api.v1.reservations.destroy', $reservation));

    $response->assertForbidden();
});

test('member can list ongoing reservations', function () {
    /** @var TestCase $this */
    ApiReservation::factory()
        ->for($this->member)
        ->create([
            'status' => 'confirmed',
            'date' => now()->addDays(2)->toDateString(),
        ]);

    ApiReservation::factory()
        ->for($this->member)
        ->create([
            'status' => 'confirmed',
            'date' => now()->subDays(2)->toDateString(),
        ]);

    $response = $this->getJson(route('api.v1.reservations.ongoing'));

    $response->assertSuccessful();
    $response->assertJsonCount(1, 'data');
    expect($response->json('data.0.status'))->toBe('confirmed');
});

test('member can list reservation history', function () {
    /** @var TestCase $this */
    ApiReservation::factory()
        ->for($this->member)
        ->create([
            'status' => 'confirmed',
            'date' => now()->addDays(2)->toDateString(),
            'payment_status' => 'paid',
        ]);

    ApiReservation::factory()
        ->for($this->member)
        ->create([
            'status' => 'confirmed',
            'date' => now()->subDays(2)->toDateString(),
            'payment_status' => 'paid',
        ]);

    ApiReservation::factory()
        ->for($this->member)
        ->create([
            'status' => 'cancelled',
            'date' => now()->subDays(5)->toDateString(),
            'payment_status' => 'pending',
        ]);

    $response = $this->getJson(route('api.v1.reservations.history'));

    $response->assertSuccessful();
    $response->assertJsonCount(1, 'data');
    expect($response->json('data.0.date'))->toBe(now()->subDays(2)->toDateString());
});
