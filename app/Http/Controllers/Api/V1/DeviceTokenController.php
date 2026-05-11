<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MemberDeviceToken;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    use ApiResponse;

    /**
     * Store or update a device token for the authenticated member.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'platform' => ['required', 'string', 'in:ios,android'],
        ]);

        $request->user()->deviceTokens()->updateOrCreate(
            ['token' => $request->token],
            ['device_type' => $request->platform]
        );

        return $this->success(null, 'Device token registered successfully');
    }
}
