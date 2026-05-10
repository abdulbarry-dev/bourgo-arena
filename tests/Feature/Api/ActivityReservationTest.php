<?php

use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('member can request an otp code', function () {
    $member = Member::factory()->create(['phone' => '21699000000']);

    $response = $this->postJson(route('api.auth.otp.request'), [
        'phone' => '21699000000',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', __('OTP code sent successfully.'));

    $this->assertDatabaseHas('otp_codes', [
        'identifier' => '21699000000',
    ]);
});

test('member can login with otp code', function () {
    $member = Member::factory()->create(['phone' => '21699000000']);
    OtpCode::factory()->create([
        'identifier' => '21699000000',
        'code' => '123456',
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->postJson(route('api.auth.otp.login'), [
        'phone' => '21699000000',
        'otp' => '123456',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'token',
                'member' => ['id', 'name', 'email', 'phone'],
            ],
            'message',
        ]);
});

test('member can list activities', function () {
    $member = Member::factory()->create();
    Sanctum::actingAs($member);

    Activity::factory()->count(3)->has(ActivitySlot::factory()->count(2), 'slots')->create();

    $response = $this->getJson(route('api.member.activities.index'));

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('member can create a reservation', function () {
    $member = Member::factory()->create(['status' => 'active']);
    Sanctum::actingAs($member);

    $activity = Activity::factory()->create(['base_price' => 50.00]);
    $slot = ActivitySlot::factory()->create([
        'activity_id' => $activity->id,
        'capacity' => 10,
        'booked_count' => 0,
    ]);

    $response = $this->postJson(route('api.member.reservations.store'), [
        'activity_id' => $activity->id,
        'activity_slot_id' => $slot->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.price', 50);

    $this->assertDatabaseHas('api_reservations', [
        'member_id' => $member->id,
        'activity_id' => $activity->id,
        'activity_slot_id' => $slot->id,
    ]);

    $this->assertEquals(1, $slot->fresh()->booked_count);
});

test('member cannot reserve a fully booked slot', function () {
    $member = Member::factory()->create();
    Sanctum::actingAs($member);

    $activity = Activity::factory()->create();
    $slot = ActivitySlot::factory()->create([
        'activity_id' => $activity->id,
        'capacity' => 10,
        'booked_count' => 10,
    ]);

    $response = $this->postJson(route('api.member.reservations.store'), [
        'activity_id' => $activity->id,
        'activity_slot_id' => $slot->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', __('This slot is already fully booked.'));
});

test('member can cancel a reservation', function () {
    $member = Member::factory()->create();
    Sanctum::actingAs($member);

    $slot = ActivitySlot::factory()->create(['booked_count' => 1]);
    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'activity_slot_id' => $slot->id,
        'status' => 'confirmed',
    ]);

    $response = $this->postJson(route('api.member.reservations.cancel', ['id' => $reservation->id]));

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'cancelled');

    $this->assertEquals(0, $slot->fresh()->booked_count);
    $this->assertNotNull($reservation->fresh()->cancelled_at);
});
