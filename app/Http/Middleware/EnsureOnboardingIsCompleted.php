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
                'message' => __('Please complete your onboarding first.'),
                'state' => 'pending_onboarding',
            ], 403);
        }

        return $next($request);
    }
}
