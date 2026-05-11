<?php

namespace App\Services\Auth;

use App\Models\Member;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
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
    public function register(array $data): Member
    {
        return Member::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => isset($data['password']) ? Hash::make($data['password']) : null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'is_family_account' => $data['is_family_account'] ?? false,
            'status' => $data['status'] ?? 'pending',
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
}
