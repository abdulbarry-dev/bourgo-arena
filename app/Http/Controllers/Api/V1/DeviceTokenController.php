<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\StoreDeviceTokenDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreDeviceTokenRequest;
use App\Http\Resources\Api\V1\DeviceTokenResource;
use App\Services\Members\MemberDeviceTokenService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class DeviceTokenController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly MemberDeviceTokenService $memberDeviceTokenService) {}

    /**
     * Store or update a device token for the authenticated member.
     */
    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $dto = StoreDeviceTokenDTO::fromRequest($validated);

        $member = $request->user();

        $deviceToken = $this->memberDeviceTokenService->register(
            $member,
            $dto,
        );

        $resource = new DeviceTokenResource($deviceToken);

        return $this->success($resource->toArray($request), 'Device token registered successfully.', 200);
    }
}
