<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\Api\V1\MemberResource;
use App\Models\Member;
use App\Services\Auth\OtpAuthService;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class OtpAuthController extends Controller
{
    public function __construct(protected OtpService $otpService, protected OtpAuthService $otpAuthService) {}

    public function requestOtp(SendOtpRequest $request): JsonResponse
    {
        $identifier = $request->input('identifier');

        try {
            $this->otpAuthService->requestOtp($identifier);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(null, __('OTP code sent successfully.'));
    }

    public function login(VerifyOtpRequest $request): JsonResponse
    {
        $identifier = $request->input('identifier');
        $code = $request->input('otp');

        try {
            $payload = $this->otpAuthService->loginWithOtp($identifier, $code);
            if (isset($payload['user']) && $payload['user'] instanceof Member) {
                $payload['user'] = new MemberResource($payload['user']);
            }
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success($payload, __('Logged in successfully.'));
    }
}
