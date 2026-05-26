<?php

namespace App\Services;

use App\Models\LoyaltyPoint;
use App\Models\Member;

class LoyaltyService
{
    public function getBalanceAndTransactions(Member $member, int $limit = 20): array
    {
        $limit = max(1, $limit);

        $transactions = LoyaltyPoint::query()
            ->where('member_id', $member->id)
            ->latest('created_at')
            ->limit($limit)
            ->get();

        return [
            'points' => (int) ($member->loyalty_points ?? 0),
            'transactions' => $transactions,
        ];
    }
}
