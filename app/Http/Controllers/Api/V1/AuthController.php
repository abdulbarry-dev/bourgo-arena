<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\CompleteOnboardingRequest;
use App\Http\Requests\Api\V1\Auth\CompleteRegistrationRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\Api\V1\MemberResource;
use App\Models\Member;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Auth\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
     *
     * @return array{success: bool, message: string, data: array{token: string, member: MemberResource}}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        try {
            $member = Member::where('email', $credentials['email'] ?? null)
                ->orWhere('phone', $credentials['phone'] ?? null)
                ->first();

            if (! $member || ! Hash::check($credentials['password'], $member->password)) {
                return $this->error(__('auth.failed'), 401);
            }

            if (! $member->isVerified()) {
                return $this->success([
                    'code' => 'EMAIL_NOT_VERIFIED',
                    'state' => 'pending_verification',
                    'member' => new MemberResource($member),
                ], __('Verification required.'), 200);
            }

            if (! $member->isOnboardingCompleted()) {
                // Issue a limited token for onboarding?
                // The prompt says "Do NOT issue full-access tokens for pending_verification users".
                // It doesn't explicitly forbid it for pending_onboarding, but it says
                // "verified but onboarding incomplete: Return: { "state": "pending_onboarding" }".
                // I'll issue a token but with limited scope if possible, or just return the state.
                $token = $member->createToken('auth_token', ['onboarding'])->plainTextToken;

                return $this->success([
                    'token' => $token,
                    'state' => 'pending_onboarding',
                    'member' => new MemberResource($member),
                ], __('Onboarding required.'), 200);
            }

            // Fully active
            $token = $member->createToken('auth_token', ['*'])->plainTextToken;

            return $this->success([
                'token' => $token,
                'state' => 'active',
                'member' => new MemberResource($member),
            ], __('Logged in successfully.'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @throttles api.auth (5 attempts per minute per IP)
     *
     * @response 429 TooManyRequestsResponse
     *
     * @return MemberResource
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $member = $this->authService->register($request->validated());

        // Generate OTP immediately
        $identifier = $member->email ?? $member->phone;
        if ($identifier) {
            $this->otpService->generate($identifier);
        }

        return $this->success([
            'member' => new MemberResource($member),
            'state' => 'pending_verification',
        ], __('Registration successful. Please verify your account.'), 201);
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
        try {
            if ($this->otpService->verify($request->identifier, $request->otp)) {
                // Find the user (Member or User/Staff)
                $user = Member::where('email', $request->identifier)
                    ->orWhere('phone', $request->identifier)
                    ->first()
                    ?? User::where('email', $request->identifier)
                        ->orWhere('phone', $request->identifier)
                        ->first();

                if ($user) {
                    $tokenAbilities = ['*'];
                    $state = 'active';

                    if ($user instanceof Member) {
                        if (! $user->isOnboardingCompleted()) {
                            $tokenAbilities = ['onboarding'];
                            $state = 'pending_onboarding';
                        }
                    }

                    $token = $user->createToken('auth_token', $tokenAbilities)->plainTextToken;

                    $responseData = [
                        'valid' => true,
                        'token' => $token,
                        'state' => $state,
                    ];

                    if ($user instanceof Member) {
                        $responseData['member'] = new MemberResource($user);
                    } else {
                        $responseData['user'] = [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->role,
                        ];
                    }

                    return $this->success($responseData, __('OTP verified successfully.'));
                }

                return $this->success([
                    'valid' => true,
                ], __('OTP verified successfully.'));
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
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

        try {
            $this->otpService->generate($identifier);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }

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
     * @throttles api.otp (3 attempts per 5 minutes per IP or identifier)
     *
     * @response 429 TooManyRequestsResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $identifier = $request->identifier;

        $user = Member::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first()
            ?? User::where('email', $identifier)
                ->orWhere('phone', $identifier)
                ->first();

        if ($user) {
            if ($user instanceof Member && ! $user->isVerified()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Your account is not verified. Please verify your account first.'),
                    'code' => 'EMAIL_NOT_VERIFIED',
                    'state' => 'pending_verification',
                ], 403);
            }

            try {
                $this->otpService->generate($identifier);
            } catch (\Exception $e) {
                return $this->error($e->getMessage(), 422);
            }
        }

        return $this->success(null, __('If an account exists with this identifier, an OTP has been sent.'));
    }

    /**
     * @throttles api.otp (3 attempts per 5 minutes per IP or identifier)
     *
     * @response 429 TooManyRequestsResponse
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $user = Member::where('email', $request->identifier)
            ->orWhere('phone', $request->identifier)
            ->first()
            ?? User::where('email', $request->identifier)
                ->orWhere('phone', $request->identifier)
                ->first();

        if ($user && $user instanceof Member && ! $user->isVerified()) {
            return response()->json([
                'success' => false,
                'message' => __('Your account is not verified.'),
                'code' => 'EMAIL_NOT_VERIFIED',
                'state' => 'pending_verification',
            ], 403);
        }

        try {
            if (! $this->otpService->verify($request->identifier, $request->otp)) {
                return $this->error(__('Invalid or expired OTP code.'), 422);
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (! $user) {
            return $this->error(__('User not found.'), 404);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->success(null, __('Password reset successfully.'));
    }

    /**
     * @throttles api.auth (5 attempts per minute per IP)
     *
     * @response 429 TooManyRequestsResponse
     *
     * @return MemberResource
     */
    public function completeRegistration(CompleteRegistrationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'],
            'is_family_account' => $validated['is_parent_account'],
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ];

        $member = $this->authService->register($data);

        return $this->success(
            new MemberResource($member),
            __('Registration completed successfully.'),
            201
        );
    }

    /**
     * Complete onboarding for a verified member.
     */
    public function completeOnboarding(CompleteOnboardingRequest $request): JsonResponse
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            return $this->error(__('Unauthorized.'), 401);
        }

        $member->update([
            ...$request->validated(),
            'onboarding_completed_at' => now(),
            'status' => 'active',
        ]);

        return $this->success([
            'member' => new MemberResource($member),
            'state' => 'active',
        ], __('Onboarding completed successfully.'));
    }
}
