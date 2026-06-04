<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogAdminAction
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
    public function handle(\App\Events\EventCanceled|\App\Events\EventDeleted $event): void
    {
        $action = 'unknown';

        if ($event instanceof \App\Events\EventCanceled) {
            $action = 'canceled_event';
        } elseif ($event instanceof \App\Events\EventDeleted) {
            $action = 'deleted_event';
        }

        \App\Models\AdminAuditLog::create([
            'admin_id' => auth()->id() ?? 1, // Fallback for tests/cli
            'event_id' => $event->event->id,
            'action' => $action,
        ]);
    }
}
