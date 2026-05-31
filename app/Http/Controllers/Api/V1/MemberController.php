<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\UpdateProfileDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\UpdateProfileRequest;
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

        $dto = UpdateProfileDTO::fromRequest($request->validated());
        $member = $this->memberService->updateProfile($member, $dto);

        $member = $this->memberRepository->loadProfileRelations($member);

        return (new MemberResource($member))->additional([
            'success' => true,
            'message' => __('Profile updated successfully.'),
        ]);
    }
}
