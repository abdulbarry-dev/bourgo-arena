<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\Auth\AuthService;
use App\Services\Auth\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuthService $authService,
        protected OtpService $otpService
    ) {}

    /**
     * Handle a login request.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            return $this->success($result, __('Logged in successfully.'));
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 401, $e->errors());
        }
    }

    /**
     * Handle a registration request.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $member = $this->authService->register($request->validated());

        return $this->success($member, __('Registration successful. Please verify your account.'), 201);
    }

    /**
     * Handle a logout request.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(null, __('Logged out successfully.'));
    }

    /**
     * Send an OTP to the provided identifier.
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $this->otpService->generate($request->identifier);

        return $this->success(null, __('OTP code sent successfully.'));
    }

    /**
     * Verify the provided OTP.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        if ($this->otpService->verify($request->identifier, $request->otp)) {
            $member = \App\Models\Member::where('email', $request->identifier)
                ->orWhere('phone', $request->identifier)
                ->first();

            if (! $member) {
                return $this->error(__('Member not found.'), 404);
            }

            $token = $member->createToken('auth_token')->plainTextToken;

            return $this->success([
                'token' => $token,
                'member' => new \App\Http\Resources\Api\V1\MemberResource($member),
            ], __('OTP verified successfully.'));
        }

        return $this->error(__('Invalid or expired OTP code.'), 422);
    }

    /**
     * Send an OTP to the authenticated member's identifier.
     */
    public function requestFamilyOtp(Request $request): JsonResponse
    {
        $member = $request->user();
        $identifier = $member->phone ?? $member->email;

        if (! $identifier) {
            return $this->error(__('No contact information found for this account.'), 422);
        }

        $this->otpService->generate($identifier);

        return $this->success(null, __('OTP code sent to your registered contact information.'));
    }

    /**
     * Update the authenticated member's password.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->updatePassword(
                $request->user(),
                $request->current_password,
                $request->password
            );

            return $this->success(null, __('Password updated successfully.'));
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }
}
