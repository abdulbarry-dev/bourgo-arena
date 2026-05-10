<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Admin\MemberResource;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMemberController extends Controller
{
    /**
     * Display a listing of members.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Member::query()
            ->with(['activeSubscription.plan'])
            ->withCount('checkInEvents');

        // Filtering
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $members = $query->paginate($request->input('per_page', 25));

        return $this->success(MemberResource::collection($members));
    }

    /**
     * Display the specified member.
     */
    public function show(Member $member): JsonResponse
    {
        $member->load([
            'activeSubscription.plan',
            'checkInEvents' => fn ($q) => $q->orderBy('created_at', 'desc')->limit(1),
        ]);

        return $this->success(new MemberResource($member));
    }

    /**
     * Update the member's status.
     */
    public function updateStatus(Request $request, Member $member): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:active,suspended,inactive'],
        ]);

        $member->update([
            'status' => $request->input('status'),
        ]);

        return $this->success(new MemberResource($member), __('Member status updated successfully.'));
    }

    /**
     * Remove the specified member from storage.
     */
    public function destroy(Member $member): JsonResponse
    {
        $member->delete();

        return $this->success(null, __('Member deleted successfully.'));
    }
}
