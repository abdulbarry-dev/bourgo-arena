<?php

namespace App\Listeners;

use App\Events\EventCanceled;
use App\Events\EventDeleted;
use App\Models\AdminAuditLog;

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
    public function handle(EventCanceled|EventDeleted $event): void
    {
        $action = 'unknown';

        if ($event instanceof EventCanceled) {
            $action = 'canceled_event';
        } elseif ($event instanceof EventDeleted) {
            $action = 'deleted_event';
        }

        AdminAuditLog::create([
            'admin_id' => auth()->id() ?? 1, // Fallback for tests/cli
            'event_id' => $event->event->id,
            'action' => $action,
        ]);
    }
}
