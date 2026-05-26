<?php

namespace App\Http\Controllers\Api\Member;

use App\DTOs\StoreDeviceTokenDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreDeviceTokenRequest;
use App\Http\Resources\Api\V1\DeviceTokenResource;
use App\Services\Members\AuthenticatedMemberResolver;
use App\Services\Members\MemberDeviceTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberDeviceTokenController extends Controller
{
    public function __construct(
        private readonly AuthenticatedMemberResolver $authenticatedMemberResolver,
        private readonly MemberDeviceTokenService $memberDeviceTokenService,
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        $member = $this->authenticatedMemberResolver->resolve($request);

        $dto = StoreDeviceTokenDTO::fromRequest($request->validated());

        $deviceToken = $this->memberDeviceTokenService->register(
            $member,
            $dto
        );

        $resource = new DeviceTokenResource($deviceToken);

        return $this->success($resource->toArray($request), 'Device token registered successfully.', $deviceToken->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Display the specified resource.
     */
    public function destroy(Request $request, string $token): JsonResponse
    {
        $member = $this->authenticatedMemberResolver->resolve($request);

        $this->memberDeviceTokenService->deactivate($member, $token);

        return $this->success(null, 'Device token deactivated');
    }
}
