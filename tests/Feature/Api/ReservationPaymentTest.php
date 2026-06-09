<?php

use App\Http\Middleware\EnsureAccountIsVerified;
use App\Http\Middleware\EnsureOnboardingIsCompleted;
use App\Models\ActivitySession;
use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Payment;
use App\Services\PaymentService;

uses()->group('api', 'reservations');

beforeEach(function () {
    $this->member = Member::factory()->create();
    $this->actingAs($this->member, 'sanctum');

    $this->withoutMiddleware([
        EnsureAccountIsVerified::class,
        EnsureOnboardingIsCompleted::class,
    ]);

    $this->session = ActivitySession::factory()->create();
});

it('creates a reservation and initiates payment', function () {
    $reservationDate = now()->addDay()->toDateString();

    $payload = [
        'activity_id' => $this->session->activity_id,
        'activity_session_id' => $this->session->id,
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

    $mock = Mockery::mock(PaymentService::class);
    $mock->shouldReceive('verify')->andReturn(['success' => true, 'status' => 'paid']);
    $this->app->instance(PaymentService::class, $mock);

    $response = $this->getJson('/api/v1/reservations/'.$reservation->id.'/payment/verify?payment_id='.$payment->id);

    $response->assertStatus(200);
});
