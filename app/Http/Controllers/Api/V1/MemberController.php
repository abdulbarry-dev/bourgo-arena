<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\UpdateProfileRequest;
use App\Http\Resources\Api\V1\CheckInEventResource;
use App\Http\Resources\Api\V1\MemberResource;
use App\Models\Member;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MemberController extends Controller
{
    use ApiResponse;

    /**
     * Get the authenticated member's profile.
     */
    public function profile(Request $request): MemberResource
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            abort(403, __('Forbidden'));
        }

        $member->load(['activeSubscription.plan', 'children'])
            ->loadCount('checkInEvents');

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

        $member->update($request->mappedData());

        $member->load(['activeSubscription.plan', 'children'])
            ->loadCount('checkInEvents');

        return (new MemberResource($member))->additional([
            'success' => true,
            'message' => __('Profile updated successfully.'),
        ]);
    }

    /**
     * Get the authenticated member's check-in/access history.
     */
    public function accessHistory(Request $request): AnonymousResourceCollection
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            abort(403, __('Forbidden'));
        }

        $history = $member->checkInEvents()
            ->with('terminal')
            ->latest('checked_in_at')
            ->get();

        return CheckInEventResource::collection($history)->additional([
            'success' => true,
            'message' => null,
        ]);
    }
}
