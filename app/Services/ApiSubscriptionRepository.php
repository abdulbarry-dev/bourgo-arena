<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Subscription;

class ApiSubscriptionRepository
{
    /**
     * Get the count of active subscriptions for a member.
     */
    public function getValidSubscriptionCount(Member $member): int
    {
        return $member->subscriptions()
            ->where('status', 'active')
            ->whereDate('ends_at', '>=', now())
            ->count();
    }

    /**
     * Get the count of active subscriptions for a list of members.
     *
     * @param  array<int, int>  $memberIds
     */
    public function getValidSubscriptionCountForMemberIds(array $memberIds): int
    {
        if ($memberIds === []) {
            return 0;
        }

        return Subscription::query()
            ->whereIn('member_id', $memberIds)
            ->where('status', 'active')
            ->whereDate('ends_at', '>=', now())
            ->count();
    }
}
