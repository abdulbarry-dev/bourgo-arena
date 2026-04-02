<?php

namespace App\Services\Members;

use App\Models\Member;
use App\Models\MemberOnboardingToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MemberOnboardingTokenService
{
    /**
     * @return array{token: string, url: string, expires_at: Carbon}
     */
    public function createForMember(Member $member, int $hours = 24): array
    {
        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = now()->addHours($hours);

        MemberOnboardingToken::query()
            ->where('member_id', $member->id)
            ->whereNull('used_at')
            ->delete();

        MemberOnboardingToken::query()->create([
            'member_id' => $member->id,
            'email' => $member->email,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'used_at' => null,
        ]);

        return [
            'token' => $plainToken,
            'url' => route('member.onboarding-password', [
                'token' => $plainToken,
                'email' => $member->email,
            ]),
            'expires_at' => $expiresAt,
        ];
    }

    public function resolveValidToken(string $plainToken): ?MemberOnboardingToken
    {
        return MemberOnboardingToken::query()
            ->with('member')
            ->where('token_hash', hash('sha256', $plainToken))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function consume(string $plainToken, string $email, string $newPassword): bool
    {
        $onboardingToken = $this->resolveValidToken($plainToken);

        if ($onboardingToken === null || $onboardingToken->member === null) {
            return false;
        }

        if (strcasecmp($onboardingToken->email, $email) !== 0) {
            return false;
        }

        DB::transaction(function () use ($onboardingToken, $newPassword): void {
            $onboardingToken->member->update([
                'password' => $newPassword,
            ]);

            $onboardingToken->update([
                'used_at' => now(),
            ]);
        });

        return true;
    }
}
