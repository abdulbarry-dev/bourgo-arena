<?php

namespace App\Listeners;

use App\Events\EventCanceled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
        $participants = \App\Models\EventParticipant::with('user')
            ->where('event_id', $eventModel->id)
            ->where('status', '!=', 'canceled')
            ->get();

        foreach ($participants as $participant) {
            $participant->update(['status' => 'canceled']);

            if ($participant->user) {
                $participant->user->notify(new \App\Notifications\EventCanceledNotification($eventModel));
            }
        }

        // 2. Flag payments for manual reconciliation
        // Payments are linked to events typically via metadata->event_id
        \App\Models\Payment::whereJsonContains('metadata->event_id', $eventModel->id)
            ->where('status', 'paid')
            ->update(['status' => 'pending_reconciliation']);
    }
}
