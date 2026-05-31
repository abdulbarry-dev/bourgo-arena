<?php

declare(strict_types=1);

namespace App\Actions\Subscriptions;

use App\Models\Member;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class TransferSubscriptionAction
{
    public function execute(Subscription $subscription, int $newMemberId, ?int $performedBy = null): Subscription
    {
        if ($newMemberId === $subscription->member_id) {
            throw new InvalidArgumentException('Cannot transfer a subscription to the same member.');
        }

        if (! Member::query()->whereKey($newMemberId)->exists()) {
            throw new InvalidArgumentException('The target member for transfer does not exist.');
        }

        if (Subscription::query()->where('member_id', $newMemberId)->active()->exists()) {
            throw new InvalidArgumentException('The target member already has an active subscription.');
        }

        if (! in_array($subscription->status, ['active', 'suspended'], true)) {
            throw new InvalidArgumentException('Only active or suspended subscriptions can be transferred.');
        }

        return DB::transaction(function () use ($subscription, $newMemberId, $performedBy): Subscription {
            $transferDate = now()->startOfDay();
            $remainingDays = $subscription->status === 'suspended'
                ? max(0, $subscription->days_remaining ?? 0)
                : $subscription->daysRemaining();
            $oldMemberId = $subscription->member_id;
            $actorId = $performedBy ?? Auth::id() ?? $subscription->enrolled_by;

            $newSubscription = Subscription::query()->create([
                'member_id' => $newMemberId,
                'plan_id' => $subscription->plan_id,
                'status' => 'active',
                'starts_at' => $transferDate->toDateString(),
                'ends_at' => Subscription::calculateEndDate($transferDate, $remainingDays),
                'suspended_at' => null,
                'days_remaining' => null,
                'resumed_at' => null,
                'payment_method' => $subscription->payment_method,
                'payment_reference' => null,
                'amount_paid' => 0,
                'receipt_path' => null,
                'enrolled_by' => $actorId,
            ]);

            $subscription->update([
                'status' => 'transferred',
                'ends_at' => $transferDate->toDateString(),
                'suspended_at' => null,
                'resumed_at' => null,
                'days_remaining' => $remainingDays,
            ]);

            $subscription->auditLogs()->create([
                'action' => 'transfer',
                'reason' => null,
                'from_member_id' => $oldMemberId,
                'to_member_id' => $newMemberId,
                'performed_by' => $actorId,
                'performed_at' => now(),
                'metadata' => [
                    'remaining_days' => $remainingDays,
                    'new_subscription_id' => $newSubscription->id,
                ],
            ]);

            return $newSubscription;
        });
    }
}
