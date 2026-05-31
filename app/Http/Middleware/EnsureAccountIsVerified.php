<?php

namespace App\Http\Middleware;

use App\Models\Member;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof Member) {
            // Check if at least one verification method is completed.
            // Members with neither email nor phone verified are blocked.
            $hasEmailVerified = $user->email_verified_at !== null;
            $hasPhoneVerified = $user->phone_verified_at !== null;
            $isVerified = $hasEmailVerified || $hasPhoneVerified;

            if (! $isVerified) {
                return response()->json([
                    'message' => __('Your account requires verification.'),
                    'code' => 'ADDITIONAL_VERIFICATION_REQUIRED',
                    'state' => 'pending_additional_verification',
                    'verification_status' => $user->getVerificationStatus(),
                ], 403);
            }
        }

        return $next($request);
    }
}
