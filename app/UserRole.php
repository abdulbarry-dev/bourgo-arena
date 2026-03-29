<?php

namespace App;

enum UserRole: string
{
    case Member = 'member';
    case Admin = 'admin';
    case Manager = 'manager';

    public function isStaff(): bool
    {
        return $this === self::Admin || $this === self::Manager;
    }
}
