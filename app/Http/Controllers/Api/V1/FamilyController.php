<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\FamilyChildDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AddChildRequest;
use App\Http\Requests\Api\V1\UpdateChildRequest;
use App\Http\Resources\Api\V1\MemberResource;
use App\Models\Member;
use App\Services\ApiFamilyService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FamilyController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ApiFamilyService $familyService
    ) {}

    /**
     * Return authenticated member's children.
     *
     * @return AnonymousResourceCollection<MemberResource>
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $children = collect($request->user()->children);

        return MemberResource::collection($children)->additional([
            'success' => true,
            'message' => null,
        ]);
    }

    /**
     * Create a new child Member record.
     */
    public function store(AddChildRequest $request): JsonResponse
    {
        $parent = $request->user();

        if (! $parent instanceof Member) {
            abort(403, __('Forbidden'));
        }

        $dto = FamilyChildDTO::fromRequest($request);

        $child = $this->familyService->createChild($parent, $dto);

        return (new MemberResource($child))->additional([
            'success' => true,
            'message' => 'Child added successfully',
        ])->response()->setStatusCode(201);
    }

    /**
     * Update an existing child member.
     */
    public function update(UpdateChildRequest $request, Member $member): JsonResponse
    {
        $parent = $request->user();

        if (! $parent instanceof Member) {
            abort(403, __('Forbidden'));
        }

        $dto = FamilyChildDTO::fromRequest($request);

        $child = $this->familyService->updateChild($parent, $member, $dto);

        if ($child === null) {
            return $this->error('Unauthorized', 403);
        }

        return (new MemberResource($child))->additional([
            'success' => true,
            'message' => 'Child updated successfully',
        ])->response();
    }

    /**
     * Enable the family account feature.
     */
    public function enableFamilyFeature(Request $request): JsonResponse
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            abort(403, __('Forbidden'));
        }

        if ($member->is_family_account) {
            return $this->error('Family account feature already enabled', 400);
        }

        $member = $this->familyService->enableFamilyAccount($member);

        return (new MemberResource($member))->additional([
            'success' => true,
            'message' => 'Family account feature enabled successfully',
        ])->response();
    }

    /**
     * Disable the family account feature.
     */
    public function disableFamilyFeature(Request $request): JsonResponse
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            abort(403, __('Forbidden'));
        }

        if (! $member->is_family_account) {
            return $this->error('Not a family account', 400);
        }

        $this->familyService->disableFamilyAccount($member);

        return $this->success(null, 'Family account feature disabled and children archived successfully');
    }

    /**
     * Delete a child member.
     */
    public function destroy(Request $request, Member $member): JsonResponse
    {
        $parent = $request->user();

        if (! $parent instanceof Member) {
            abort(403, __('Forbidden'));
        }

        if (! $this->familyService->deleteChild($parent, $member)) {
            return $this->error('Unauthorized', 403);
        }

        return $this->success(null, 'Child removed successfully');
    }
}
