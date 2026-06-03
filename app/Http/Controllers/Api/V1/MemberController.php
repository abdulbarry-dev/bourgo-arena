<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\UpdateProfileDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\UpdateProfileRequest;
use App\Http\Requests\Member\UploadMemberAvatarRequest;
use App\Http\Resources\Api\V1\MemberResource;
use App\Models\Member;
use App\Repositories\Members\MemberRepository;
use App\Services\Members\MemberService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected MemberRepository $memberRepository,
        protected MemberService $memberService
    ) {}

    /**
     * Get the authenticated member's profile.
     */
    public function profile(Request $request): MemberResource
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            abort(403, __('Forbidden'));
        }

        $member = $this->memberRepository->loadProfileRelations($member);

        return (new MemberResource($member))->additional([
            'success' => true,
            'message' => null,
        ]);
    }

    /**
     * Update the authenticated member's profile.
     */
    public function updateProfile(UpdateProfileRequest $request): MemberResource
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            abort(403, __('Forbidden'));
        }

        if ($request->hasFile('avatar')) {
            $member = $this->memberService->uploadAvatar($member, $request->file('avatar'));
        }

        $dto = UpdateProfileDTO::fromRequest($request->mappedData());

        if ($dto->toArray() !== []) {
            $member = $this->memberService->updateProfile($member, $dto);
        }

        $member = $this->memberRepository->loadProfileRelations($member);

        return (new MemberResource($member))->additional([
            'success' => true,
            'message' => __('Profile updated successfully.'),
        ]);
    }

    /**
     * Upload or replace the authenticated member's profile avatar.
     */
    public function uploadAvatar(UploadMemberAvatarRequest $request): MemberResource
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            abort(403, __('Forbidden'));
        }

        $member = $this->memberService->uploadAvatar($member, $request->file('avatar'));
        $member = $this->memberRepository->loadProfileRelations($member);

        return (new MemberResource($member))->additional([
            'success' => true,
            'message' => __('Profile photo updated successfully.'),
        ]);
    }

    /**
     * Remove the authenticated member's profile avatar.
     */
    public function deleteAvatar(Request $request): MemberResource
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            abort(403, __('Forbidden'));
        }

        $member = $this->memberService->deleteAvatar($member);
        $member = $this->memberRepository->loadProfileRelations($member);

        return (new MemberResource($member))->additional([
            'success' => true,
            'message' => __('Profile photo removed successfully.'),
        ]);
    }
}
