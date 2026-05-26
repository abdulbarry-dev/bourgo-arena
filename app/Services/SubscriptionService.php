<?php

namespace App\Services;

class SubscriptionService
{
    public function getActiveForUser($user)
    {
        return $user->activeSubscription()->with('plan')->first();
    }
}
