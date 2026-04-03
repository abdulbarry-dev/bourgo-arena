<?php

use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('checkins', function (User $user): bool {
    $role = $user->role?->value ?? (string) $user->role;

    return in_array($role, [
        UserRole::Admin->value,
        UserRole::Manager->value,
    ], true);
});
