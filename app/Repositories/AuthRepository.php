<?php

namespace App\Repositories;

use App\Models\Member;
use App\Models\User;

class AuthRepository
{
    public function findMemberByIdentifier(?string $email, ?string $phone): ?Member
    {
        $query = Member::query();

        if ($email) {
            $query->where('email', $email);
        } elseif ($phone) {
            $query->where('phone', $phone);
        } else {
            return null;
        }

        return $query->first();
    }

    public function findMemberOrUserByIdentifier(string $identifier)
    {
        return Member::where('email', $identifier)->orWhere('phone', $identifier)->first()
            ?? User::where('email', $identifier)->orWhere('phone', $identifier)->first();
    }
}
