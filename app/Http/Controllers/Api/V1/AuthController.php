<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuthService $authService,
        protected OtpService $otpService
    ) {}

    /**
     * @throttles api.auth (5 attempts per minute per IP or account identifier)
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

            $verificationStatus = $member->getVerificationStatus();
            $state = 'active';
            $code = null;
            $message = __('Logged in successfully.');

            if (! $member->isFullyVerified()) {
                if ($member->isVerified()) {
                    $state = 'pending_additional_verification';
                    $code = 'ADDITIONAL_VERIFICATION_REQUIRED';
                    $message = __('Your account requires additional verification.');
                } else {
                    $state = 'pending_verification';
                    $code = 'EMAIL_NOT_VERIFIED';
                    $message = __('Your account is not verified. Please verify your email/phone.');
                }

                return $this->success([
                    'code' => $code,
                    'state' => $state,
                    'user' => new MemberResource($member),
                    'verification_status' => $verificationStatus,
                ], $message);
            }

            if (! $member->isOnboardingCompleted()) {
                $token = $member->createToken('auth_token', ['onboarding'])->plainTextToken;

                return $this->success([
                    'token' => $token,
                    'state' => 'pending_onboarding',
                    'user' => new MemberResource($member),
                    'verification_status' => $verificationStatus,
                ], __('Please complete your profile to continue.'), 200);
            }

            // Fully active
            $token = $member->createToken('auth_token', ['*'])->plainTextToken;

            return $this->success([
                'token' => $token,
                'state' => 'active',
                'user' => new MemberResource($member),
                'verification_status' => $verificationStatus,
            ], $message);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @throttles api.auth (5 attempts per minute per IP)
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $member = $this->authService->register($request->validated());

        // Generate OTP immediately
        $identifier = $member->email ?? $member->phone;
        if ($identifier) {
            $this->otpService->generate($identifier);
        }

        return $this->success(
            [
                'user' => new MemberResource($member),
                'state' => 'pending_verification',
                'verification_status' => $member->getVerificationStatus(),
            ],
            __('Registration successful. Please verify your email/phone.'),
            201
        );
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
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $this->otpService->generate($request->identifier);

        return $this->success(null, __('OTP code sent successfully.'));
    }

    /**
     * @throttles api.otp (3 attempts per 5 minutes per IP or identifier)
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
                    $state = $user instanceof Member ? $user->status : 'active';
                    $abilities = ['*'];

                    if ($user instanceof Member) {
                        if ($user->status === 'pending_verification' || $user->status === 'pending_additional_verification') {
                            $abilities = ['verification'];
                        } elseif ($user->status === 'pending_onboarding') {
                            $abilities = ['onboarding'];
                        }
                    }

                    $token = $user->createToken('auth_token', $abilities)->plainTextToken;

                    return $this->success([
                        'valid' => true,
                        'token' => $token,
                        'state' => $state,
                        'user' => $user instanceof Member ? new MemberResource($user) : $user,
                        'verification_status' => $user instanceof Member ? $user->getVerificationStatus() : null,
                    ], __('OTP verified successfully.'));
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
     */
    public function completeRegistration(CompleteRegistrationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $member = $request->user();

        $member->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'],
            'is_family_account' => $validated['is_parent_account'],
            'pin' => $validated['pin'],
            'status' => 'active',
            'onboarding_completed_at' => now(),
        ]);

        // Revoke current token and issue a new one with full abilities
        $member->tokens()->delete();
        $token = $member->createToken('auth_token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'state' => 'active',
            'user' => new MemberResource($member),
        ], __('Registration completed successfully.'), 201);
    }

    public function verificationStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof Member) {
            return $this->error(__('Only members have verification status.'), 403);
        }

        return $this->success($user->getVerificationStatus());
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['nullable', 'string'],
        ]);

        $member = $request->user();

        if (! $member instanceof Member) {
            return $this->error(__('Only members can verify email.'), 403);
        }

        if ($member->email !== $request->email) {
            return $this->error(__('Email does not match your account.'), 422);
        }

        if (! $request->has('otp')) {
            $this->otpService->generate($request->email);

            return $this->success(null, __('OTP Sent'));
        }

        try {
            if ($this->otpService->verify($request->email, $request->otp)) {
                $member->refresh();

                $abilities = ['verification'];
                if ($member->status === 'pending_onboarding') {
                    $abilities = ['onboarding'];
                } elseif ($member->status === 'active') {
                    $abilities = ['*'];
                }

                // Revoke current token and issue a new one with updated abilities
                $request->user()->currentAccessToken()->delete();
                $token = $member->createToken('auth_token', $abilities)->plainTextToken;

                return $this->success([
                    'valid' => true,
                    'token' => $token,
                    'state' => $member->status,
                    'verification_status' => $member->getVerificationStatus(),
                ], __('Email verified successfully.'));
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->error(__('Invalid or expired OTP code.'), 422);
    }

    public function verifyPhone(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'otp' => ['required', 'string'],
        ]);

        $member = $request->user();

        if (! $member instanceof Member) {
            return $this->error(__('Only members can verify phone.'), 403);
        }

        if ($member->phone !== $request->phone) {
            return $this->error(__('Phone number does not match your account.'), 422);
        }

        try {
            if ($this->otpService->verify($request->phone, $request->otp)) {
                $member->refresh();

                $abilities = ['verification'];
                if ($member->status === 'pending_onboarding') {
                    $abilities = ['onboarding'];
                } elseif ($member->status === 'active') {
                    $abilities = ['*'];
                }

                // Revoke current token and issue a new one with updated abilities
                $request->user()->currentAccessToken()->delete();
                $token = $member->createToken('auth_token', $abilities)->plainTextToken;

                return $this->success([
                    'valid' => true,
                    'token' => $token,
                    'state' => $member->status,
                    'verification_status' => $member->getVerificationStatus(),
                ], __('Phone verified successfully.'));
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->error(__('Invalid or expired OTP code.'), 422);
    }

    public function skipAdditionalVerification(Request $request): JsonResponse
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            return $this->error(__('Only members can skip verification.'), 403);
        }

        if ($member->status !== 'pending_additional_verification') {
            return $this->error(__('You are not in a state where additional verification can be skipped.'), 403);
        }

        $member->update(['status' => 'pending_onboarding']);

        // Revoke current token and issue a new one with onboarding ability
        $token = $member->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        $token = $member->createToken('auth_token', ['onboarding'])->plainTextToken;

        return $this->success([
            'token' => $token,
            'state' => 'pending_onboarding',
        ], __('Additional verification skipped.'));
    }
}
