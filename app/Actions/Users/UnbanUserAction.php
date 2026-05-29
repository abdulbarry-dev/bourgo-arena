<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;

final class UnbanUserAction
{
    public function execute(User $user): void
    {
        $user->update([
            'banned_at' => null,
            'ban_reason' => null,
        ]);
    }
}
