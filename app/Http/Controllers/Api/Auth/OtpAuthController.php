<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
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
        $member = Member::where('phone', $phone)->first();

        if (! $member) {
            return $this->error(__('Member not found with this phone number.'), 404);
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

        $member = Member::where('phone', $phone)->firstOrFail();
        $token = $member->createToken('mobile-app')->plainTextToken;

        return $this->success([
            'token' => $token,
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
            ],
        ], __('Logged in successfully.'));
    }
}
