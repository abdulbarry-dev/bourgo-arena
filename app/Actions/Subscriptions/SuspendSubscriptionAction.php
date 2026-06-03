<?php

declare(strict_types=1);

namespace App\Actions\Subscriptions;

use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;

final class SuspendSubscriptionAction
{
    public function execute(Subscription $subscription, ?int $performedBy = null): void
    {
        $remainingDays = $subscription->daysRemaining();

        $subscription->update([
            'status' => 'suspended',
            'days_remaining' => $remainingDays,
            'suspended_at' => now(),
            'resumed_at' => null,
        ]);

        $subscription->auditLogs()->create([
            'action' => 'suspend',
            'reason' => null,
            'from_member_id' => $subscription->member_id,
            'to_member_id' => null,
            'performed_by' => $performedBy ?? Auth::id(),
            'performed_at' => now(),
            'metadata' => ['remaining_days' => $remainingDays],
        ]);
    }
}
