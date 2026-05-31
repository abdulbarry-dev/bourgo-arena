<?php

namespace App\Http\Middleware;

use App\Models\Member;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingIsCompleted
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof Member && ! $user->isOnboardingCompleted()) {
            return response()->json([
                'message' => __('Must complete onboarding to access your account.'),
                'code' => 'ONBOARDING_INCOMPLETE',
                'state' => 'pending_onboarding',
                'required_action' => 'complete_onboarding',
                'cta' => __('Complete Setup'),
            ], 403);
        }

        return $next($request);
    }
}
