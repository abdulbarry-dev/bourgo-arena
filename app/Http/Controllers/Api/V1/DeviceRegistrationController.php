<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterDeviceRequest;
use App\Http\Resources\DeviceAccessTokenResource;
use App\Models\DeviceAccessToken;
use App\Services\DeviceAttestationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceRegistrationController extends Controller
{
    public function __construct(
        private readonly DeviceAttestationService $attestationService,
    ) {}

    public function register(RegisterDeviceRequest $request): JsonResponse
    {
        if ($request->platform !== 'web' && ! $this->attestationService->verifyAppVersion($request->app_version, $request->platform)) {
            return response()->json([
                'message' => 'App version '.$request->app_version.' is no longer supported. Please update to the latest version.',
                'required_version' => config('app.min_app_version.'.$request->platform),
            ], 422);
        }

        $integrityPassed = true;
        if ($request->platform !== 'web') {
            $integrityPassed = $this->attestationService->verify(
                $request->integrity_token,
                $request->platform,
            );
        }

        if (! $integrityPassed) {
            return response()->json([
                'message' => 'Device integrity verification failed. Please install from the official app store.',
            ], 422);
        }

        $token = Str::random(64);
        $ttlDays = (int) config('app.device_token_ttl', 30);

        $existingToken = DeviceAccessToken::query()
            ->forDevice($request->device_id)
            ->first();

        if ($existingToken) {
            $existingToken->update([
                'token' => $token,
                'device_fingerprint' => $request->device_fingerprint,
                'platform' => $request->platform,
                'app_version' => $request->app_version,
                'integrity_passed' => $integrityPassed,
                'integrity_payload' => $request->integrity_token,
                'ip_address' => $request->ip(),
                'expires_at' => now()->addDays($ttlDays),
                'is_revoked' => false,
                'revoked_at' => null,
            ]);

            return response()->json([
                'data' => new DeviceAccessTokenResource($existingToken->fresh()),
            ], 201);
        }

        $deviceToken = DeviceAccessToken::create([
            'device_id' => $request->device_id,
            'token' => $token,
            'device_fingerprint' => $request->device_fingerprint,
            'platform' => $request->platform,
            'app_version' => $request->app_version,
            'integrity_passed' => $integrityPassed,
            'integrity_payload' => $request->integrity_token,
            'ip_address' => $request->ip(),
            'expires_at' => now()->addDays($ttlDays),
        ]);

        return response()->json([
            'data' => new DeviceAccessTokenResource($deviceToken),
        ], 201);
    }

    public function refresh(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        $deviceToken = DeviceAccessToken::query()
            ->forToken($token)
            ->active()
            ->first();

        if (! $deviceToken) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $newToken = Str::random(64);
        $ttlDays = (int) config('app.device_token_ttl', 30);

        $deviceToken->update([
            'token' => $newToken,
            'expires_at' => now()->addDays($ttlDays),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'data' => [
                'token' => $newToken,
                'expires_at' => $deviceToken->expires_at,
            ],
        ]);
    }

    public function link(Request $request): JsonResponse
    {
        $deviceId = $request->input('device_id');

        if (! $deviceId) {
            return response()->json(['message' => 'device_id is required'], 422);
        }

        $member = $request->user('sanctum');

        if (! $member) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $deviceToken = DeviceAccessToken::query()
            ->forDevice($deviceId)
            ->active()
            ->first();

        if (! $deviceToken) {
            return response()->json(['message' => 'Device not found or token expired'], 404);
        }

        $deviceToken->update([
            'member_id' => $member->id,
            'last_verified_at' => now(),
        ]);

        return response()->json(['message' => 'Device linked successfully']);
    }

    public function logout(Request $request): JsonResponse
    {
        $deviceId = $request->input('device_id');

        if (! $deviceId) {
            return response()->json(['message' => 'device_id is required'], 422);
        }

        $deviceToken = DeviceAccessToken::query()
            ->forDevice($deviceId)
            ->active()
            ->first();

        if ($deviceToken) {
            $deviceToken->update([
                'is_revoked' => true,
                'revoked_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Device logged out successfully']);
    }
}
