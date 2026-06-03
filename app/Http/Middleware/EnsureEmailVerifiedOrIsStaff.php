<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerifiedOrIsStaff
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            Log::info('Email verification check', [
                'user_id' => $user->id,
                'is_staff' => $user->isStaff(),
                'role' => $user->role->value ?? $user->role,
            ]);

            // If they are staff (Admin/Manager), skip verification
            if ($user->isStaff()) {
                return $next($request);
            }

            return $request->expectsJson()
                ? abort(403, 'Your email address is not verified.')
                : Redirect::guest(URL::route('verification.notice'));
        }

        return $next($request);
    }
}
