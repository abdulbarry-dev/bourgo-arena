<?php

use App\Contracts\PaymentProviderInterface;
use App\Models\Activity;
use App\Models\ActivityTimeSlot;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Payment\PaymentManager;
use App\Services\ReservationService;
use Illuminate\Validation\ValidationException;

test('createReservation locks slot capacity and calculates deposit', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['base_price' => 123.450]);

    $slot = ActivityTimeSlot::query()->create([
        'activity_id' => $activity->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'max_capacity' => 2,
        'reserved_count' => 0,
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

    $slot->refresh();
    expect($slot->reserved_count)->toBe(1);
    expect($slot->is_available)->toBeTrue();
});

test('createReservation prevents duplicate reservation for same user and slot', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['base_price' => 90]);

    $slot = ActivityTimeSlot::query()->create([
        'activity_id' => $activity->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '12:00:00',
        'end_time' => '13:00:00',
        'max_capacity' => 5,
        'reserved_count' => 0,
        'is_available' => true,
    ]);

    $service = app(ReservationService::class);

    $service->createReservation($user, $activity->id, $slot->id, true, 'konnect');

    expect(fn () => $service->createReservation($user, $activity->id, $slot->id, true, 'konnect'))
        ->toThrow(ValidationException::class);
});

test('cancelReservation updates status and decrements slot count', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['base_price' => 70]);

    $slot = ActivityTimeSlot::query()->create([
        'activity_id' => $activity->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '14:00:00',
        'end_time' => '15:00:00',
        'max_capacity' => 2,
        'reserved_count' => 1,
        'is_available' => false,
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
        'refund_status' => 'not_requested',
    ]);

    $updated = app(ReservationService::class)->cancelReservation($reservation, 'schedule conflict');

    expect($updated->reservation_status)->toBe('cancelled');
    expect($updated->cancellation_reason)->toBe('schedule conflict');

    $slot->refresh();
    expect($slot->reserved_count)->toBe(0);
    expect($slot->is_available)->toBeTrue();
});

test('cancelReservation triggers gateway refund and logs transaction when payment completed', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['base_price' => 100]);

    $slot = ActivityTimeSlot::query()->create([
        'activity_id' => $activity->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '16:00:00',
        'end_time' => '17:00:00',
        'max_capacity' => 2,
        'reserved_count' => 1,
        'is_available' => true,
    ]);

    $reservation = Reservation::query()->create([
        'user_id' => $user->id,
        'activity_id' => $activity->id,
        'activity_time_slot_id' => $slot->id,
        'reservation_status' => 'confirmed',
        'payment_status' => 'completed',
        'deposit_amount' => 10,
        'full_amount' => 100,
        'payment_gateway' => 'konnect',
        'transaction_reference' => 'txn_ref_001',
        'refund_status' => 'not_requested',
    ]);

    $providerMock = Mockery::mock(PaymentProviderInterface::class);
    $providerMock->shouldReceive('refund')
        ->once()
        ->with('txn_ref_001', 10.0)
        ->andReturn([
            'success' => true,
            'refund_id' => 'refund_001',
        ]);

    $paymentManagerMock = Mockery::mock(PaymentManager::class);
    $paymentManagerMock->shouldReceive('driver')
        ->once()
        ->with('konnect')
        ->andReturn($providerMock);

    $this->app->instance(PaymentManager::class, $paymentManagerMock);

    $updated = app(ReservationService::class)->cancelReservation($reservation, 'member request');

    expect($updated->reservation_status)->toBe('cancelled');
    expect($updated->payment_status)->toBe('refunded');
    expect($updated->refund_status)->toBe('completed');

    $this->assertDatabaseHas('payment_transactions', [
        'reservation_id' => $reservation->id,
        'payment_gateway' => 'konnect',
        'refund_reference' => 'refund_001',
        'transaction_status' => 'refund_completed',
    ]);
});
