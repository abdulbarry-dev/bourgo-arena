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

        if ($user instanceof Member && ! $user->isVerified()) {
            return response()->json([
                'message' => __('Your account is not verified.'),
                'code' => 'EMAIL_NOT_VERIFIED',
                'state' => 'pending_verification',
            ], 403);
        }

        return $next($request);
    }
}
