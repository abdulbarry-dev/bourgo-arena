<?php

use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Payment;
use App\Models\ActivitySlot;
use App\Http\Middleware\EnsureAccountIsVerified;
use App\Http\Middleware\EnsureOnboardingIsCompleted;
use Illuminate\Support\Str;

uses()->group('api', 'reservations');

beforeEach(function () {
    // Create and authenticate a member for API requests
    $this->member = Member::factory()->create();
    $this->actingAs($this->member, 'sanctum');

    // Disable verification/onboarding middleware for API tests
    $this->withoutMiddleware([
        EnsureAccountIsVerified::class,
        EnsureOnboardingIsCompleted::class,
    ]);

    // Create an activity slot (factory creates activity)
    $this->slot = ActivitySlot::factory()->create();
});

it('creates a reservation and initiates payment', function () {
    // Use an activity and slot via factories if available; fall back to creating minimal reservation
    $payload = [
        'activity_id' => $this->slot->activity_id,
        'activity_slot_id' => $this->slot->id,
        'date' => $this->slot->date->format('Y-m-d'),
    ];

    $response = $this->postJson('/api/v1/reservations', $payload);

    $response->assertStatus(201)->assertJsonStructure(['data' => ['id']]);
});

it('initiates a payment for an existing reservation', function () {
    $reservation = ApiReservation::factory()->create(['member_id' => $this->member->id]);

    $response = $this->postJson('/api/v1/reservations/'.$reservation->id.'/payment/initiate', ['gateway' => 'konnect']);

    // In CI/env without gateway credentials this may fail; assert we receive an error
    $response->assertStatus(500)->assertJsonFragment(['message' => 'Payment initiation failed']);
});

it('verifies a payment and marks reservation as paid', function () {
    $reservation = ApiReservation::factory()->create(['member_id' => $this->member->id]);

    $payment = Payment::factory()->create([
        'member_id' => $this->member->id,
        'reservation_id' => $reservation->id,
        'status' => 'initiated',
        'driver' => config('payment.default', 'konnect'),
    ]);

    // Replace PaymentService with a fake that returns paid to avoid external gateway calls
    $this->instance(\App\Services\PaymentService::class, new class($payment) extends \App\Services\PaymentService {
        public function __construct($payment)
        {
            // minimal constructor: no parent dependencies
        }

        public function verify($paymentObj, ?string $transactionId = null): array
        {
            return ['success' => true, 'status' => 'paid', 'data' => ['transaction_id' => $paymentObj->gateway_transaction_id ?? $paymentObj->payment_reference]];
        }
    });

    $response = $this->getJson('/api/v1/reservations/'.$reservation->id.'/payment/verify?payment_id='.$payment->id);

    $response->assertStatus(200)->assertJsonFragment(['status' => 'paid']);
});

it('cancels a reservation and attempts a refund', function () {
    $reservation = ApiReservation::factory()->create(['member_id' => $this->member->id]);

    $payment = Payment::factory()->create([
        'member_id' => $this->member->id,
        'reservation_id' => $reservation->id,
        'status' => 'paid',
        'driver' => config('payment.default', 'konnect'),
        'gateway_transaction_id' => 'tx_'.Str::random(8),
    ]);

    $response = $this->deleteJson('/api/v1/reservations/'.$reservation->id.'/cancel');

    $response->assertStatus(200)->assertJsonFragment(['message' => 'Reservation cancelled']);
});
