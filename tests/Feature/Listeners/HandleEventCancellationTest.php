<?php

use App\Events\EventCanceled;
use App\Listeners\HandleEventCancellation;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\EventCanceledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('updates participants to canceled and sends notifications', function () {
    Notification::fake();

    $event = Event::factory()->create();

    $user = User::factory()->create();
    $participant = EventParticipant::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => 'registered',
    ]);

    $listener = new HandleEventCancellation();
    $listener->handle(new EventCanceled($event));

    $participant->refresh();

    // Verify status was updated
    expect($participant->status)->toBe('canceled');

    // Verify notification was sent to user
    Notification::assertSentTo($user, EventCanceledNotification::class);
});

it('flags associated payments as pending_reconciliation', function () {
    $event = Event::factory()->create();

    $payment = Payment::factory()->create([
        'status' => 'paid',
        'metadata' => ['event_id' => $event->id],
    ]);

    $listener = new HandleEventCancellation();
    $listener->handle(new EventCanceled($event));

    $payment->refresh();

    // Verify payment was flagged for manual review
    expect($payment->status)->toBe('pending_reconciliation');
});
