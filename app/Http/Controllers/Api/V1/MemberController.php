<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\UpdateProfileRequest;
use App\Http\Resources\Api\V1\MemberResource;
use App\Models\Member;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    use ApiResponse;

    /**
     * Get the authenticated member's profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            return $this->error(__('Forbidden'), 403);
        }

        $member->load(['activeSubscription.plan', 'children'])
            ->loadCount('checkInEvents');

        return $this->success(new MemberResource($member));
    }

    /**
     * Update the authenticated member's profile.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            return $this->error(__('Forbidden'), 403);
        }

        $member->update($request->mappedData());

        $member->load(['activeSubscription.plan', 'children'])
            ->loadCount('checkInEvents');

        return $this->success(new MemberResource($member), __('Profile updated successfully.'));
    }
}
