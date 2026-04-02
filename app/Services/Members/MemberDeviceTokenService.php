<?php

namespace App\Services\Members;

use App\Models\Member;
use App\Models\MemberDeviceToken;

class MemberDeviceTokenService
{
    public function register(Member $member, string $token, ?string $deviceType): MemberDeviceToken
    {
        return MemberDeviceToken::query()->updateOrCreate(
            ['token' => $token],
            [
                'member_id' => $member->id,
                'provider' => 'fcm',
                'device_type' => $deviceType,
                'is_active' => true,
                'last_used_at' => now(),
            ],
        );
    }

    public function deactivate(Member $member, string $token): void
    {
        MemberDeviceToken::query()
            ->where('member_id', $member->id)
            ->where('token', $token)
            ->update([
                'is_active' => false,
                'last_used_at' => now(),
            ]);
    }
}
