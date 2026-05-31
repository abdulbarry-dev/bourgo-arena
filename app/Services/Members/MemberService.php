<?php

namespace App\Services\Members;

use App\DTOs\UpdateProfileDTO;
use App\Models\Member;
use App\Notifications\AccountDeletionScheduled;
use App\Repositories\Members\MemberRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

    public function uploadAvatar(Member $member, UploadedFile $file): Member
    {
        $this->deleteStoredAvatarFile($member);

        $path = $file->store('members/avatars', 'public');

        return $this->memberRepository->updateProfile($member, ['avatar' => $path]);
    }

    public function deleteAvatar(Member $member): Member
    {
        $this->deleteStoredAvatarFile($member);

        return $this->memberRepository->updateProfile($member, ['avatar' => null]);
    }

    private function deleteStoredAvatarFile(Member $member): void
    {
        if (blank($member->avatar) || filter_var($member->avatar, FILTER_VALIDATE_URL)) {
            return;
        }

        Storage::disk('public')->delete($member->avatar);
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
