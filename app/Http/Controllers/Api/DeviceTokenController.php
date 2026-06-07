<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Store or update the FCM device token.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'device_type' => ['nullable', 'string', 'in:ios,android,web'],
        ]);

        $user = $request->user();

        // Update or create the token for this user.
        $user->deviceTokens()->updateOrCreate(
            ['token' => $request->token],
            ['device_type' => $request->device_type]
        );

        return response()->json(['message' => 'Device token updated successfully.']);
    }
}
