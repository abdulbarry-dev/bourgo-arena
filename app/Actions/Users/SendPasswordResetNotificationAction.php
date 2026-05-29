<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use App\Notifications\QueuedResetPassword;

final class SendPasswordResetNotificationAction
{
    public function execute(User $user, string $token): void
    {
        $user->notify(new QueuedResetPassword($token));
    }
}
