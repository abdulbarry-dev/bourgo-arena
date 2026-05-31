<?php

declare(strict_types=1);

namespace App\Actions\Subscriptions;

use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;

final class ResumeSubscriptionAction
{
    public function execute(Subscription $subscription, ?int $performedBy = null): void
    {
        $remaining = max(0, $subscription->days_remaining ?? $subscription->daysRemaining());
        $resumedAt = now();

        $subscription->update([
            'status' => 'active',
            'ends_at' => Subscription::calculateEndDate($resumedAt, $remaining),
            'resumed_at' => $resumedAt,
            'suspended_at' => null,
            'days_remaining' => null,
        ]);

        $subscription->auditLogs()->create([
            'action' => 'resume',
            'reason' => null,
            'from_member_id' => $subscription->member_id,
            'to_member_id' => null,
            'performed_by' => $performedBy ?? Auth::id(),
            'performed_at' => now(),
            'metadata' => ['restored_days' => $remaining],
        ]);
    }
}
