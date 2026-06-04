<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyBracketPublishedJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public \App\Models\Event $event)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $participants = \App\Models\EventParticipant::with('user')
            ->where('event_id', $this->event->id)
            ->where('status', '!=', 'canceled')
            ->get();

        foreach ($participants as $participant) {
            if ($participant->user) {
                $participant->user->notify(new \App\Notifications\BracketPublishedNotification($this->event));
            }
        }
    }
}
