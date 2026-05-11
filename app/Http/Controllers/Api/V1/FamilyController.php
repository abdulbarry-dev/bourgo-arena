<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AddChildRequest;
use App\Http\Resources\Api\V1\MemberResource;
use App\Models\Member;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    use ApiResponse;

    /**
     * Return authenticated member's children.
     */
    public function index(Request $request): JsonResponse
    {
        $children = collect($request->user()->children);

        return $this->success(MemberResource::collection($children));
    }

    /**
     * Create a new child Member record.
     */
    public function store(AddChildRequest $request): JsonResponse
    {
        $child = $request->user()->children()->create([
            'name' => $request->validated('name'),
            'date_of_birth' => $request->validated('date_of_birth'),
            'gender' => $request->validated('gender'),
            'status' => 'active',
            'password' => null,
        ]);

        return $this->success(new MemberResource($child), 'Child added successfully', 201);
    }

    /**
     * Delete a child member.
     */
    public function destroy(Request $request, Member $member): JsonResponse
    {
        if ($member->parent_id !== $request->user()->id) {
            return $this->error('Unauthorized', 403);
        }

        $member->delete();

        return $this->success(null, 'Child removed successfully');
    }
}
