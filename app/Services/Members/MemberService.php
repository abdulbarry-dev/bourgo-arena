<?php

namespace App\Services\Members;

use App\DTOs\UpdateProfileDTO;
use App\Models\Member;
use App\Notifications\AccountDeletionScheduled;
use App\Repositories\Members\MemberRepository;

class MemberService
{
    public function __construct(
        protected MemberRepository $memberRepository
    ) {}

    /**
     * Update member profile with mapped data and return refreshed model.
     */
    public function updateProfile(Member $member, UpdateProfileDTO $dto): Member
    {
        return $this->memberRepository->updateProfile($member, $dto->toArray());
    }

    /**
     * Schedule account deletion and notify the member.
     */
    public function scheduleAccountDeletion(Member $member, int $hours = 48): void
    {
        $this->memberRepository->scheduleDeletion($member, now()->addHours($hours));

        $member->notify(new AccountDeletionScheduled);

        // Revoke all tokens
        $member->tokens()->delete();
    }
}
