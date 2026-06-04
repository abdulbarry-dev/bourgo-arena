<?php

namespace App\Services;

use App\Models\Subscription;

class SubscriptionService
{
    public function getActiveForUser($member): ?Subscription
    {
        return $member->validSubscriptions()->with('plan')->orderByDesc('ends_at')->first();
    }
}
