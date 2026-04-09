<?php

namespace App\Jobs;

use App\Models\AdminAlert;
use App\Models\Member;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SuspendMemberJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $memberId,
        public string $cardUid,
        public int $terminalId
    ) {}

    public function handle(): void
    {
        $member = Member::find($this->memberId);

        if (! $member) {
            return;
        }

        // Suspend the member due to fraud
        $member->status = 'SUSPENDED_FRAUD';
        $member->save();

        if ($member->nfcCard) {
            $member->nfcCard->status = 'suspended';
            $member->nfcCard->save();
        }

        // Generate a high priority admin alert
        AdminAlert::create([
            'terminal_id' => $this->terminalId,
            'member_id' => $this->memberId,
            'alert_type' => 'FRAUD_SUSPENSION',
            'description' => "Member {$member->name} ({$this->cardUid}) was automatically suspended after 3 passback violations.",
            'count' => 3,
            'is_dismissed' => false,
        ]);

        // Dispatch a push notification to members could be added here
        Log::warning("Member [{$member->id}] suspended automatically by SuspendMemberJob (Passback Fraud).");
    }
}
