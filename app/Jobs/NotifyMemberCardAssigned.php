<?php

namespace App\Jobs;

use App\Models\Member;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class NotifyMemberCardAssigned implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $memberId)
    {
        $this->onQueue('notifications');
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
        $member = Member::query()
            ->with('nfcCard')
            ->find($this->memberId);

        if ($member === null) {
            return;
        }

        $cardUid = $member->nfcCard?->uid ?? 'N/A';
        $cardStatus = $member->nfcCard?->status ?? 'N/A';

        Mail::raw(
            "Your NFC card has been assigned. UID: {$cardUid}. Status: {$cardStatus}.",
            function ($message) use ($member): void {
                $message
                    ->to($member->email)
                    ->subject('Your Bourgo Arena NFC card has been assigned');
            },
        );
    }
}
