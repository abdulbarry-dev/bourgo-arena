<?php

namespace App\Services\Members;

use App\DTOs\UpdateProfileDTO;
use App\Models\Member;
use App\Notifications\AccountDeletionScheduled;
use App\Repositories\Members\MemberRepository;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     * Return the member's check-in/access history with terminal relation.
     *
     * @return Collection
     */
    public function getAccessHistory(Member $member)
    {
        return $this->memberRepository->getAccessHistory($member);
    }

    /**
     * Determine fallback methods for NFC based on physical card presence.
     *
     * @return string[]
     */
    public function getNfcFallbackMethods(Member $member): array
    {
        $methods = ['pin'];

        if ($member->nfcCard()->where('status', 'active')->exists()) {
            $methods[] = 'physical_card';
        }

        return $methods;
    }
}
