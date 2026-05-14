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

        if ($user instanceof Member && ! $user->isFullyVerified()) {
            $state = $user->isVerified() ? 'pending_additional_verification' : 'pending_verification';
            $code = $user->isVerified() ? 'ADDITIONAL_VERIFICATION_REQUIRED' : 'EMAIL_NOT_VERIFIED';

            return response()->json([
                'message' => __('Your account requires verification.'),
                'code' => $code,
                'state' => $state,
                'verification_status' => $user->getVerificationStatus(),
            ], 403);
        }

        return $next($request);
    }
}
