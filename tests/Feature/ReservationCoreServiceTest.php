<?php

use App\Models\Activity;
use App\Models\ActivityTimeSlot;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Validation\ValidationException;

test('createReservation creates a reservation and calculates deposit', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['base_price' => 123.450]);

    $slot = ActivityTimeSlot::query()->create([
        'activity_id' => $activity->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'max_capacity' => 2,
        'is_available' => true,
    ]);

    $reservation = app(ReservationService::class)->createReservation(
        user: $user,
        activityId: $activity->id,
        timeSlotId: $slot->id,
        requiresPayment: true,
        paymentGateway: 'konnect',
    );

    expect($reservation->reservation_status)->toBe('pending_payment');
    expect($reservation->payment_status)->toBe('not_initiated');
    expect((float) $reservation->deposit_amount)->toBe(12.345);
    expect((float) $reservation->full_amount)->toBe(123.45);
});

test('createReservation prevents duplicate reservation for same slot', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $activity = Activity::factory()->create(['base_price' => 90]);

    $slot = ActivityTimeSlot::query()->create([
        'activity_id' => $activity->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '12:00:00',
        'end_time' => '13:00:00',
        'max_capacity' => 5,
        'is_available' => true,
    ]);

    $service = app(ReservationService::class);

    $service->createReservation($otherUser, $activity->id, $slot->id, true, 'konnect');

    expect(fn () => $service->createReservation($user, $activity->id, $slot->id, true, 'konnect'))
        ->toThrow(ValidationException::class);
});

test('cancelReservation updates status', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['base_price' => 70]);

    $slot = ActivityTimeSlot::query()->create([
        'activity_id' => $activity->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '14:00:00',
        'end_time' => '15:00:00',
        'max_capacity' => 2,
        'is_available' => true,
    ]);

    $reservation = Reservation::query()->create([
        'user_id' => $user->id,
        'activity_id' => $activity->id,
        'activity_time_slot_id' => $slot->id,
        'reservation_status' => 'confirmed',
        'payment_status' => 'not_initiated',
        'deposit_amount' => 7,
        'full_amount' => 70,
        'payment_gateway' => 'konnect',
        'transaction_reference' => null,
    ]);

    $updated = app(ReservationService::class)->cancelReservation($reservation, 'schedule conflict');

    expect($updated->reservation_status)->toBe('cancelled');
    expect($updated->cancellation_reason)->toBe('schedule conflict');
});
