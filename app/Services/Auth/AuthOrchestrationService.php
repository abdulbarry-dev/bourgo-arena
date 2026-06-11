<?php

namespace App\Services\Auth;

use App\DTOs\Auth\LoginDTO;
use App\Models\Member;
use App\Models\User;
use App\Repositories\AuthRepository;
use Illuminate\Validation\ValidationException;

class AuthOrchestrationService
{
    public function __construct(
        protected AuthService $authService,
        protected OtpService $otpService,
        protected AuthRepository $authRepository
    ) {}

    /**
     * Handle login flow and return payload for controller to return.
     *
     * @return array{token: string, state: string, user: Member, code?: string, verification_status?: mixed, required_action?: string, cta?: string, upcoming_schedule?: array}
     *
     * @throws ValidationException
     */
    public function login(LoginDTO $dto): array
    {
        $email = $dto->email;
        $phone = $dto->phone;

        $member = $this->authRepository->findMemberByIdentifier($email, $phone);

        if (! $member || ! app('hash')->check($dto->password, $member->password)) {
            throw ValidationException::withMessages(['email' => [__('auth.failed')]]);
        }

        $state = $member->status ?? $member->state;
        $verificationStatus = $member->getVerificationStatus();

        $member->load(['children', 'validSubscriptions.plan.service']);
        $upcomingSchedule = app(AuthDashboardService::class)->buildUpcomingSchedule($member);

        if ($member->scheduled_for_deletion_at && $member->scheduled_for_deletion_at->isFuture()) {
            $identifier = $member->phone ?? $member->email;
            $this->otpService->generate($identifier);

            $token = $member->createToken('auth_token', ['deletion-cancellation'])->plainTextToken;

            return [
                'token' => $token,
                'state' => 'pending_deletion_cancellation',
                'code' => 'ACCOUNT_DELETION_PENDING',
                'user' => $member,
                'verification_status' => $verificationStatus,
                'upcoming_schedule' => $upcomingSchedule,
            ];
        }

        if (in_array($state, ['pending_verification', 'pending_additional_verification'], true)) {
            $token = $member->createToken('auth_token', ['verification'])->plainTextToken;
            $responseState = $state === 'pending_verification' ? 'pending_additional_verification' : $state;
            $code = $responseState === 'pending_additional_verification' ? 'ADDITIONAL_VERIFICATION_REQUIRED' : 'EMAIL_NOT_VERIFIED';

            return [
                'token' => $token,
                'state' => $responseState,
                'code' => $code,
                'user' => $member,
                'verification_status' => $verificationStatus,
                'upcoming_schedule' => $upcomingSchedule,
            ];
        }

        if (! $member->isVerified()) {
            $token = $member->createToken('auth_token', ['verification'])->plainTextToken;

            return [
                'token' => $token,
                'state' => 'pending_additional_verification',
                'code' => 'ADDITIONAL_VERIFICATION_REQUIRED',
                'user' => $member,
                'verification_status' => $verificationStatus,
                'upcoming_schedule' => $upcomingSchedule,
            ];
        }

        if (! $member->isOnboardingCompleted()) {
            $token = $member->createToken('auth_token', ['onboarding'])->plainTextToken;

            return [
                'token' => $token,
                'state' => 'pending_onboarding',
                'code' => 'ONBOARDING_INCOMPLETE',
                'required_action' => 'complete_onboarding',
                'cta' => __('Complete Setup'),
                'user' => $member,
                'verification_status' => $verificationStatus,
                'upcoming_schedule' => $upcomingSchedule,
            ];
        }

        $token = $member->createToken('auth_token', ['*'])->plainTextToken;

        return [
            'token' => $token,
            'state' => $state,
            'user' => $member,
            'verification_status' => $verificationStatus,
            'upcoming_schedule' => $upcomingSchedule,
        ];
    }

    /**
     * Verify OTP and issue token if applicable.
     *
     *
     * @throws ValidationException
     */
    public function verifyOtp(string $identifier, string $otp): array
    {
        try {
            $valid = $this->otpService->verify($identifier, $otp);
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['otp' => [$e->getMessage()]]);
        }

        if (! $valid) {
            throw ValidationException::withMessages(['otp' => [__('Invalid or expired OTP code.')]]);
        }

        $user = $this->authRepository->findMemberOrUserByIdentifier($identifier);

        if (! $user) {
            return ['valid' => true];
        }

        $state = $user instanceof Member ? ($user->status ?? $user->state) : 'active';
        $abilities = ['*'];

        if ($user instanceof Member) {
            $userState = $user->status ?? $user->state;
            if (in_array($userState, ['pending_verification', 'pending_additional_verification'], true)) {
                $abilities = ['verification'];
            } elseif ($userState === 'pending_onboarding') {
                $abilities = ['onboarding'];
            }
        }

        $token = $user->createToken('auth_token', $abilities)->plainTextToken;

        $response = [
            'valid' => true,
            'token' => $token,
            'state' => $state,
            'user' => $user,
            'verification_status' => $user instanceof Member ? $user->getVerificationStatus() : null,
        ];

        if ($user instanceof Member) {
            $user->load(['children', 'validSubscriptions.plan.service']);
            $response['upcoming_schedule'] = app(AuthDashboardService::class)->buildUpcomingSchedule($user);
        }

        return $response;
    }
}
