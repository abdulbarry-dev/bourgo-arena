<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtpAuthController extends Controller
{
    public function __construct(protected OtpService $otpService) {}

    public function requestOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
        ]);

        $phone = $request->input('phone');
        $user = Member::where('phone', $phone)->first()
            ?? User::where('phone', $phone)->first();

        if (! $user) {
            return $this->error(__('User not found with this phone number.'), 404);
        }

        $code = $this->otpService->generate($phone);
        $this->otpService->send($phone, $code);

        return $this->success(null, __('OTP code sent successfully.'));
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $phone = $request->input('phone');
        $code = $request->input('otp');

        if (! $this->otpService->verify($phone, $code)) {
            return $this->error(__('Invalid or expired OTP code.'), 422);
        }

        $user = Member::where('phone', $phone)->first()
            ?? User::where('phone', $phone)->first();

        if (! $user) {
            return $this->error(__('User not found.'), 404);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user instanceof Member ? 'member' : $user->role,
            ],
        ], __('Logged in successfully.'));
    }
}
