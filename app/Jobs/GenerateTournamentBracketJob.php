<?php

namespace App\Jobs;

use App\Actions\Events\GenerateTournamentBracketAction;
use App\Models\Event;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateTournamentBracketJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Event $event) {}

    /**
     * Execute the job.
     */
    public function handle(GenerateTournamentBracketAction $action): void
    {
        $action->execute($this->event);
    }
}
