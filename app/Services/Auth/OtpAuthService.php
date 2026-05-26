<?php

namespace App\Services\Auth;

use App\Models\Member;
use App\Models\User;
use App\Repositories\AuthRepository;
use Illuminate\Validation\ValidationException;

class OtpAuthService
{
    public function __construct(
        protected OtpService $otpService,
        protected AuthRepository $authRepository
    ) {}

    /**
     * Request an OTP for the given identifier (phone or email).
     */
    public function requestOtp(string $identifier): void
    {
        $this->otpService->generate($identifier);
    }

    /**
     * Verify OTP and return authentication payload.
     *
     * @return array{token: string, state: string, user: array}
     *
     * @throws ValidationException
     */
    public function loginWithOtp(string $identifier, string $code): array
    {
        try {
            $verified = $this->otpService->verify($identifier, $code);
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['otp' => [$e->getMessage()]]);
        }

        if (! $verified) {
            throw ValidationException::withMessages(['otp' => [__('Invalid or expired OTP code.')]]);
        }

        $user = $this->authRepository->findMemberOrUserByIdentifier($identifier);

        if (! $user) {
            throw ValidationException::withMessages(['identifier' => [__('User not found.')]]);
        }

        $tokenAbilities = ['*'];
        $state = 'active';

        if ($user instanceof Member) {
            if (! $user->isOnboardingCompleted()) {
                $tokenAbilities = ['onboarding'];
                $state = 'pending_onboarding';
            }
        }

        $token = $user->createToken('mobile-app', $tokenAbilities)->plainTextToken;

        return [
            'token' => $token,
            'state' => $state,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user instanceof Member ? 'member' : $user->role,
            ],
        ];
    }
}
