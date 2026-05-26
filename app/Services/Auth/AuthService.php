<?php

namespace App\Services\Auth;

use App\DTOs\Auth\CompleteRegistrationDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Models\Member;
use App\Models\User;
use App\Repositories\AuthRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    public function __construct(
        protected OtpService $otpService,
        protected AuthRepository $authRepository
    ) {}

    /**
     * Verify an identifier (email or phone) via OTP. If no OTP provided, generate one.
     *
     *
     * @throws ValidationException
     */
    public function verifyIdentifier(Member $member, string $identifier, ?string $otp, string $type = 'email'): array
    {
        if ($member->{$type} !== $identifier) {
            throw ValidationException::withMessages([
                $type => [__(ucfirst($type).' does not match your account.')],
            ]);
        }

        if (! $otp) {
            $this->otpService->generate($identifier);

            return ['generated' => true];
        }

        try {
            if (! $this->otpService->verify($identifier, $otp)) {
                throw ValidationException::withMessages(['otp' => [__('Invalid or expired OTP code.')]]);
            }
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['otp' => [$e->getMessage()]]);
        }

        $member->refresh();

        $abilities = ['verification'];
        $memberState = $member->status ?? $member->state;
        if ($memberState === 'pending_onboarding') {
            $abilities = ['onboarding'];
        } elseif ($memberState === 'active') {
            $abilities = ['*'];
        }

        $token = $member->currentAccessToken();
        if (method_exists($token, 'delete')) {
            $token->delete();
        }

        $token = $member->createToken('auth_token', $abilities)->plainTextToken;

        return [
            'valid' => true,
            'token' => $token,
            'state' => $memberState,
            'verification_status' => $member->getVerificationStatus(),
        ];
    }

    /**
     * Authenticate a member and return token and member object.
     *
     * @param  array{email?: string, phone?: string, password: string}  $credentials
     * @return array{token: string, member: Member}
     *
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        $query = Member::query();

        if (isset($credentials['email'])) {
            $query->where('email', $credentials['email']);
        } elseif (isset($credentials['phone'])) {
            $query->where('phone', $credentials['phone']);
        }

        $member = $query->first();

        if (! $member || ! Hash::check($credentials['password'], $member->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $token = $member->createToken('auth_token')->plainTextToken;

        return [
            'token' => $token,
            'member' => $member,
        ];
    }

    /**
     * Register a new member.
     */
    public function register(RegisterDTO $dto): Member
    {
        return Member::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'password' => $dto->password ? Hash::make($dto->password) : null,
            'date_of_birth' => $dto->date_of_birth,
            'gender' => $dto->gender,
            'is_family_account' => $dto->is_family_account,
            // New registrations start in pending verification state
            'status' => 'pending_verification',
            'state' => 'pending_verification',
        ]);
    }

    /**
     * Logout the member by revoking current token.
     */
    public function logout(Member $member): void
    {
        $token = $member->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /**
     * Update member password.
     *
     * @throws ValidationException
     */
    public function updatePassword(Member $member, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $member->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('The provided password does not match our records.')],
            ]);
        }

        $member->update([
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * Complete member onboarding and issue a full-access token.
     *
     * @return array{token: string, member: Member}
     */
    public function completeRegistration(Member $member, CompleteRegistrationDTO $dto): array
    {
        $member->update([
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'date_of_birth' => $dto->date_of_birth,
            'gender' => $dto->gender,
            'is_family_account' => $dto->is_parent_account,
            'pin' => $dto->pin,
            'status' => 'active',
            'state' => 'active',
            'onboarding_completed_at' => now(),
        ]);

        $member->refresh();

        $member->tokens()->delete();
        $token = $member->createToken('auth_token')->plainTextToken;

        return ['token' => $token, 'member' => $member];
    }

    /**
     * Reset a user's password (used after OTP verification).
     */
    public function resetPasswordByOtp($user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * Find a user (Member or User) by email or phone identifier.
     *
     * @return Member|User|null
     */
    public function findUserByIdentifier(string $identifier)
    {
        return $this->authRepository->findMemberOrUserByIdentifier($identifier);
    }

    /**
     * Skip additional verification and issue an onboarding token.
     *
     * @return array{token: string, member: Member}
     */
    public function skipAdditionalVerification(Member $member): array
    {
        $member->update(['status' => 'pending_onboarding', 'state' => 'pending_onboarding']);

        $member->refresh();

        $token = $member->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        $token = $member->createToken('auth_token', ['onboarding'])->plainTextToken;

        return ['token' => $token, 'member' => $member];
    }
}
