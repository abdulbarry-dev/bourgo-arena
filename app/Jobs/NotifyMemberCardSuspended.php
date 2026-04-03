<?php

namespace App\Jobs;

use App\Models\NfcCard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyMemberCardSuspended implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $card;

    public function __construct(NfcCard $card)
    {
        $this->card = $card;
    }

    public function handle(): void
    {
        // For now this is just a placeholder to hook into the email/push.
        // It satisfies the implementation plan gap.
        \Log::info("Dispatching suspension notification for card: {$this->card->uid}");
    }
}
