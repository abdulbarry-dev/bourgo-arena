<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionService
{
    public function getActiveForUser(User $user): ?Subscription
    {
        return $user->validSubscriptions()->with('plan')->orderByDesc('ends_at')->first();
    }
}
