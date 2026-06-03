<?php

use App\Jobs\ReconcilePaymentJob;
use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Payment;
use App\Services\LoyaltyCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('creates a reconciliation record when webhook marks payment as paid', function () {
    Event::fake();

    $member = Member::factory()->create();
    $activity = Activity::factory()->create();
    $slot = ActivitySlot::factory()->create(['activity_id' => $activity->id, 'starts_at' => '10:00:00', 'ends_at' => '11:00:00', 'capacity' => 4, 'booked_count' => 1]);

    $reservation = ApiReservation::factory()->for($member)->forActivity($activity)->forSlot($slot)->create(['payment_status' => 'pending', 'status' => 'confirmed']);

    $payment = Payment::factory()->create(['member_id' => $member->id, 'reservation_id' => $reservation->id, 'status' => 'pending', 'amount' => $reservation->price]);

    // Bind a quiet loyalty service so job can call it without side-effects
    $loyaltyMock = Mockery::mock(LoyaltyCalculatorService::class);
    $loyaltyMock->shouldReceive('creditVariableForReservation')->andReturnNull();
    $this->app->instance(LoyaltyCalculatorService::class, $loyaltyMock);

    $payload = ['status' => 'paid', 'payment_id' => 'TXN-123', 'provider' => 'test'];

    ReconcilePaymentJob::dispatchSync($payment->id, $payload);

    $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
    $this->assertDatabaseHas('payment_reconciliations', ['payment_id' => $payment->id, 'type' => 'reconciled']);
});

it('creates a reconciliation record when webhook marks payment as refunded', function () {
    $member = Member::factory()->create();
    $activity = Activity::factory()->create();
    $slot = ActivitySlot::factory()->create(['activity_id' => $activity->id, 'starts_at' => '10:00:00', 'ends_at' => '11:00:00', 'capacity' => 4, 'booked_count' => 1]);

    $reservation = ApiReservation::factory()->for($member)->forActivity($activity)->forSlot($slot)->create(['payment_status' => 'paid', 'status' => 'confirmed']);

    $payment = Payment::factory()->create(['member_id' => $member->id, 'reservation_id' => $reservation->id, 'status' => 'paid', 'amount' => $reservation->price]);

    $payload = ['status' => 'refunded', 'refund_amount' => ($payment->amount / 2), 'provider' => 'test'];

    ReconcilePaymentJob::dispatchSync($payment->id, $payload);

    $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'refunded']);
    $this->assertDatabaseHas('payment_reconciliations', ['payment_id' => $payment->id, 'type' => 'refunded', 'amount' => ($payment->amount / 2)]);
});
