<?php

namespace App\Services;

use App\Models\Member;
use App\Models\User;

class NotificationService
{
    public function markAllRead(Member|User $user): int
    {
        if (! method_exists($user, 'notifications')) {
            return 0;
        }

        return $user->notifications()->where('is_read', false)->update(['is_read' => true]);
    }
}
