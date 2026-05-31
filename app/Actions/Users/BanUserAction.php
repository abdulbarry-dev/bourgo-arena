<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;

final class BanUserAction
{
    public function execute(User $user, string $reason): void
    {
        $user->update([
            'banned_at' => now(),
            'ban_reason' => $reason,
        ]);
    }
}
