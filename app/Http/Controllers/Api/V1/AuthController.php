<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\CompleteRegistrationRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\Api\V1\MemberResource;
use App\Models\Member;
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
     * @throttles api.auth (5 attempts per minute per IP or account identifier)
     *
     * @response 429 TooManyRequestsResponse
     * @return array{success: bool, message: string, data: array{token: string, member: MemberResource}}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            $data = [
                'token' => $result['token'],
                'member' => new MemberResource($result['member']),
            ];

            return $this->success($data, __('Logged in successfully.'));
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 401, $e->errors());
        }
    }

    /**
     * @throttles api.auth (5 attempts per minute per IP)
     *
     * @response 429 TooManyRequestsResponse
     * @return MemberResource
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $member = $this->authService->register($request->validated());

        return (new MemberResource($member))->additional([
            'success' => true,
            'message' => __('Registration successful. Please verify your account.'),
        ])->response()->setStatusCode(201);
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
     * @throttles api.otp (3 attempts per 5 minutes per IP or identifier)
     *
     * @response 429 TooManyRequestsResponse
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $this->otpService->generate($request->identifier);

        return $this->success(null, __('OTP code sent successfully.'));
    }

    /**
     * @throttles api.otp (3 attempts per 5 minutes per IP or identifier)
     *
     * @response 429 TooManyRequestsResponse
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        if ($this->otpService->verify($request->identifier, $request->otp)) {
            $member = Member::where('email', $request->identifier)
                ->orWhere('phone', $request->identifier)
                ->first();

            if (! $member) {
                return $this->error(__('Member not found.'), 404);
            }

            $token = $member->createToken('auth_token')->plainTextToken;

            return $this->success([
                'valid' => true,
                'token' => $token,
                'member' => new MemberResource($member),
            ], __('OTP verified successfully.'));
        }

        return $this->error(__('Invalid or expired OTP code.'), 422);
    }

    /**
     * @throttles api.otp (3 attempts per 5 minutes per user)
     *
     * @response 429 TooManyRequestsResponse
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
     * @throttles api.password (5 attempts per minute per user)
     *
     * @response 429 TooManyRequestsResponse
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->updatePassword(
                $request->user(),
                $request->current_password,
                $request->new_password ?? $request->password
            );

            return $this->success(null, __('Password updated successfully.'));
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * @throttles api.auth (5 attempts per minute per IP)
     *
     * @response 429 TooManyRequestsResponse
     * @return MemberResource
     */
    public function completeRegistration(CompleteRegistrationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Map is_parent_account to is_family_account for internal logic
        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'is_family_account' => $validated['is_parent_account'],
            'status' => 'active',
        ];

        $member = $this->authService->register($data);

        return (new MemberResource($member))->additional([
            'success' => true,
            'message' => __('Registration completed successfully.'),
        ])->response()->setStatusCode(201);
    }
}
