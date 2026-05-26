<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Auth\CompleteRegistrationDTO;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
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
use App\Services\Auth\AuthOrchestrationService;
use App\Services\Auth\AuthService;
use App\Services\Auth\OtpService;
use App\Services\Members\MemberService;
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
        protected OtpService $otpService,
        protected AuthOrchestrationService $orchestration
    ) {}

    /**
     * @throttles api.auth (5 attempts per minute per IP or account identifier)
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $dto = LoginDTO::fromRequest($request);

        try {
            $payload = $this->orchestration->login($dto);

            if (isset($payload['user']) && $payload['user'] instanceof Member) {
                $payload['user'] = new MemberResource($payload['user']);
            }

            $message = match ($payload['state'] ?? null) {
                'pending_onboarding' => __('Must complete onboarding to unlock your account.'),
                default => __('Login successful.'),
            };

            return $this->success($payload, $message);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 401);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @throttles api.auth (5 attempts per minute per IP)
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = RegisterDTO::fromRequest($request);
        $member = $this->authService->register($dto);

        // Generate OTP immediately
        $identifier = $member->email ?? $member->phone;
        if ($identifier) {
            $this->otpService->generate($identifier);
        }

        $token = $member->createToken('auth_token', ['verification'])->plainTextToken;

        return $this->success(
            [
                'token' => $token,
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
            $payload = $this->orchestration->verifyOtp($request->identifier, $request->otp);

            if (isset($payload['user']) && $payload['user'] instanceof Member) {
                $payload['user'] = new MemberResource($payload['user']);
            }

            return $this->success($payload, __('OTP verified successfully.'));
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * @throttles api.otp (3 attempts per 5 minutes per user)
     */
    public function requestFamilyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'method' => ['sometimes', 'string', 'in:email,phone,sms'],
        ]);

        $member = $request->user();

        if (! $member instanceof Member) {
            return $this->error(__('Only members can request family OTP.'), 403);
        }

        $method = $request->input('method');
        $identifier = null;

        if ($method === 'email') {
            if (! $member->email || ! $member->email_verified_at) {
                return $this->error(__('Your email is not verified.'), 422);
            }
            $identifier = $member->email;
            $preferred = 'mail';
        } elseif ($method === 'phone' || $method === 'sms') {
            if (! $member->phone || ! $member->phone_verified_at) {
                return $this->error(__('Your phone number is not verified.'), 422);
            }
            $identifier = $member->phone;
            $preferred = 'sms';
        } else {
            // Default logic: prioritize verified phone, then verified email
            if ($member->phone && $member->phone_verified_at) {
                $identifier = $member->phone;
                $preferred = 'sms';
            } elseif ($member->email && $member->email_verified_at) {
                $identifier = $member->email;
                $preferred = 'mail';
            } else {
                // Fallback to whatever is available if nothing is verified yet
                $identifier = $member->phone ?? $member->email;
                $preferred = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'mail' : 'sms';
            }
        }

        if (! $identifier) {
            return $this->error(__('No contact information found for this account.'), 422);
        }

        try {
            $this->otpService->generate($identifier, $preferred ?? null);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(null, __('OTP code sent to your registered :method.', [
            'method' => filter_var($identifier, FILTER_VALIDATE_EMAIL) ? __('email') : __('phone number'),
        ]));
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

        $user = $this->authService->findUserByIdentifier($identifier);

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
        $user = $this->authService->findUserByIdentifier($request->identifier);

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

        $this->authService->resetPasswordByOtp($user, $request->password);

        return $this->success(null, __('Password reset successfully.'));
    }

    /**
     * @throttles api.auth (5 attempts per minute per IP)
     */
    public function completeRegistration(CompleteRegistrationRequest $request): JsonResponse
    {
        $dto = CompleteRegistrationDTO::fromRequest($request);
        $member = $request->user();

        $result = $this->authService->completeRegistration($member, $dto);

        return $this->success([
            'token' => $result['token'],
            'state' => $result['member']->status ?? $result['member']->state,
            'user' => new MemberResource($result['member']),
            'verification_status' => $result['member']->getVerificationStatus(),
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

        try {
            $result = $this->authService->verifyIdentifier($member, $request->email, $request->otp ?? null, 'email');

            if (isset($result['generated']) && $result['generated'] === true) {
                return $this->success(null, __('OTP Sent'));
            }

            return $this->success([
                'valid' => true,
                'token' => $result['token'],
                'state' => $result['state'],
                'verification_status' => $result['verification_status'],
            ], __('Email verified successfully.'));
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
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

        try {
            $result = $this->authService->verifyIdentifier($member, $request->phone, $request->otp, 'phone');

            return $this->success([
                'valid' => true,
                'token' => $result['token'],
                'state' => $result['state'],
                'verification_status' => $result['verification_status'],
            ], __('Phone verified successfully.'));
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function skipAdditionalVerification(Request $request): JsonResponse
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            return $this->error(__('Only members can skip verification.'), 403);
        }

        if (! $member->isVerified() || $member->isFullyVerified()) {
            return $this->error(__('You are not in a state where additional verification can be skipped.'), 403);
        }

        $result = $this->authService->skipAdditionalVerification($member);

        return $this->success([
            'token' => $result['token'],
            'state' => 'pending_onboarding',
            'verification_status' => $result['member']->getVerificationStatus(),
        ], __('Additional verification skipped.'));
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $member = $request->user();

        if (! Hash::check($request->password, $member->password)) {
            return $this->error(__('The provided password does not match our records.'), 422);
        }

        app(MemberService::class)->scheduleAccountDeletion($member);

        return $this->success(null, __('Your account has been scheduled for deletion in 48 hours. You can cancel this by logging back in before then.'));
    }
}
