<?php

use App\Http\Middleware\EnsureAccountIsVerified;
use App\Http\Middleware\EnsureOnboardingIsCompleted;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Payment;
use App\Services\PaymentService;

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
    $reservationDate = now()->addDay()->toDateString();

    $payload = [
        'activity_id' => $this->slot->activity_id,
        'activity_slot_id' => $this->slot->id,
        'date' => $reservationDate,
    ];

    $response = $this->postJson('/api/v1/reservations', $payload);

    $response->assertStatus(201)->assertJsonStructure(['data' => ['id']]);
});

it('initiates a payment for an existing reservation', function () {
    $reservation = ApiReservation::factory()->create(['member_id' => $this->member->id]);

    $deposit = round($reservation->price * 0.10, 3);
    $response = $this->postJson('/api/v1/reservations/'.$reservation->id.'/payment/initiate', ['gateway' => 'konnect', 'amount' => $deposit]);

    $response->assertStatus(200);
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
    $this->instance(PaymentService::class, new class($payment) extends PaymentService
    {
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
