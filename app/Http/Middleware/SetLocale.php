<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = ['en', 'fr'];
        $locale = config('app.locale');

        // Session preference has highest priority (e.g. Set by dashboard UI)
        if (session()->has('locale') && in_array(session('locale'), $supportedLocales)) {
            $locale = session('locale');
        }
        // Fallback to Accept-Language header for APIs or first-time visits
        elseif ($request->hasHeader('Accept-Language')) {
            $headerLocale = substr($request->header('Accept-Language'), 0, 2);
            if (in_array($headerLocale, $supportedLocales)) {
                $locale = $headerLocale;
            }
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
