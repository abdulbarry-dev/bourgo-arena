<?php

namespace App\Repositories\Members;

use App\Models\Member;
use App\Models\MemberDeviceToken;

class MemberDeviceTokenRepository
{
    public function findByToken(string $token): ?MemberDeviceToken
    {
        return MemberDeviceToken::query()->where('token', $token)->first();
    }

    public function upsertForMember(Member $member, string $token, ?string $deviceType): MemberDeviceToken
    {
        return MemberDeviceToken::query()->updateOrCreate(
            ['token' => $token],
            [
                'member_id' => $member->id,
                'provider' => 'fcm',
                'device_type' => $deviceType,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );
    }

    public function deactivateForMember(Member $member, string $token): int
    {
        return MemberDeviceToken::query()
            ->where('member_id', $member->id)
            ->where('token', $token)
            ->update([
                'is_active' => false,
                'last_used_at' => now(),
            ]);
    }
}
