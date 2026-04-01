<?php

namespace App\Jobs;

use App\Models\HikvisionTerminal;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncTerminalWhitelist implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public int $memberId,
        public ?int $subscriptionId = null,
        public array $context = [],
    ) {
        $this->onQueue('default');
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $terminalIds = HikvisionTerminal::query()
            ->where('status', '!=', 'decommissioned')
            ->pluck('id')
            ->all();

        Log::info('Terminal whitelist sync placeholder queued', [
            'member_id' => $this->memberId,
            'subscription_id' => $this->subscriptionId,
            'terminal_ids' => $terminalIds,
            'context' => $this->context,
        ]);
    }
}
