<?php

namespace App\Listeners;

use App\Events\PassbackViolationDetected;
use App\Jobs\SuspendMemberJob;
use App\Models\AdminAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Redis;

class HandlePassbackViolation implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PassbackViolationDetected $event): void
    {
        $memberId = $event->event->member_id;
        $cardUid = $event->event->card_uid;
        $terminalId = $event->event->terminal_id;

        if (! $memberId) {
            return;
        }

        $key = "passback_violations:{$cardUid}";
        $count = Redis::incr($key);
        Redis::expire($key, 86400 * 7); // expire in 7 days

        // Create Admin Alert for the dashboard visibility
        AdminAlert::create([
            'terminal_id' => $terminalId,
            'member_id' => $memberId,
            'alert_type' => 'PASSBACK_VIOLATION',
            'description' => "Passback violation #{$count} detected for card {$cardUid}.",
            'count' => $count,
        ]);

        if ($count >= 3) {
            // Auto Suspension Flow
            SuspendMemberJob::dispatch($memberId, $cardUid, $terminalId);

            // Wipe Redis Violation count after escalating to suspension
            Redis::del($key);
        }
    }
}
