<?php

namespace App\Listeners;

use App\Events\EventCanceled;
use App\Models\EventParticipant;
use App\Models\Payment;
use App\Notifications\EventCanceledNotification;

class HandleEventCancellation
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(EventCanceled $event): void
    {
        $eventModel = $event->event;

        // 1. Mark all participants as canceled and notify them
        $participants = EventParticipant::with('user')
            ->where('event_id', $eventModel->id)
            ->where('status', '!=', 'canceled')
            ->get();

        foreach ($participants as $participant) {
            $participant->update(['status' => 'canceled']);

            if ($participant->user) {
                $participant->user->notify(new EventCanceledNotification($eventModel));
            }
        }

        // 2. Flag payments for manual reconciliation
        // Payments are linked to events typically via metadata->event_id
        Payment::whereJsonContains('metadata->event_id', $eventModel->id)
            ->where('status', 'paid')
            ->update(['status' => 'pending_reconciliation']);
    }
}
