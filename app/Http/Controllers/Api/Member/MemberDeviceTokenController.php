<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreDeviceTokenRequest;
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

        $validated = $request->validated();

        $deviceToken = $this->memberDeviceTokenService->register(
            $member,
            $validated['token'],
            $validated['device_type'] ?? null,
        );

        return response()->json([
            'message' => 'Device token registered successfully.',
            'data' => [
                'id' => $deviceToken->id,
                'token' => $deviceToken->token,
                'device_type' => $deviceToken->device_type,
                'is_active' => $deviceToken->is_active,
            ],
        ], $deviceToken->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Display the specified resource.
     */
    public function destroy(Request $request, string $token): JsonResponse
    {
        $member = $this->authenticatedMemberResolver->resolve($request);

        $this->memberDeviceTokenService->deactivate($member, $token);

        return response()->json([], 204);
    }
}
