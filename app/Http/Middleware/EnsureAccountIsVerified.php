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
            // `status` is the authoritative DB column for account state; fall back
            // to `state` if `status` is not set on the model instance.
            $state = $user->status ?? $user->state ?? null;

            if (in_array($state, ['pending_verification', 'pending_additional_verification'])) {
                // Normalize blocked verification state for API clients so both
                // pending states funnel to the additional verification screen.
                $responseState = $state === 'pending_verification' ? 'pending_additional_verification' : $state;
                $code = 'ADDITIONAL_VERIFICATION_REQUIRED';

                return response()->json([
                    'message' => __('Your account requires verification.'),
                    'code' => $code,
                    'state' => $responseState,
                    'verification_status' => $user->getVerificationStatus(),
                ], 403);
            }
        }

        return $next($request);
    }
}
