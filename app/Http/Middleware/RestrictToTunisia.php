<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\GeoLocationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RestrictToTunisia
{
    public function __construct(
        protected GeoLocationService $geoLocationService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('geo.enabled')) {
            return $next($request);
        }

        if ($request->routeIs('*webhook*')) {
            return $next($request);
        }

        if ($this->isExempt($request)) {
            return $this->trackIp($request, $next);
        }

        try {
            $geo = $this->geoLocationService->detect($request);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => 'geo_lookup_failed',
                'message' => 'Payment security verification unavailable. Please try again later.',
            ], 403);
        }

        if (! $geo->isTunisian()) {
            Log::warning('Payment blocked: non-Tunisian IP', [
                'ip' => $geo->ip,
                'country' => $geo->countryCode,
                'route' => $request->route()?->getName(),
                'user_id' => $request->user()?->id,
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'geo_restricted',
                'country' => $geo->countryCode,
                'message' => 'Payments are only available from Tunisia.',
            ], 403);
        }

        return $this->trackIp($request, $next);
    }

    private function isExempt(Request $request): bool
    {
        if (! config('geo.block_local_ips', false) && $this->isLocalNetwork($request->ip())) {
            return true;
        }

        $user = $request->user();

        if (config('geo.exempt_staff', true) && $user instanceof User && $user->isStaff()) {
            return true;
        }

        return false;
    }

    private function isLocalNetwork(?string $ip): bool
    {
        if ($ip === null) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
    }

    private function trackIp(Request $request, Closure $next): Response
    {
        $member = $request->user();

        if ($member && method_exists($member, 'getAuthIdentifier')) {
            $rotationMinutes = config('geo.rotation_detection_minutes', 5);

            if (
                $member->last_payment_ip
                && $member->last_payment_ip !== $request->ip()
                && $member->last_payment_at
                && $member->last_payment_at->diffInMinutes(now()) < $rotationMinutes
            ) {
                Log::warning('Suspicious IP rotation detected', [
                    'member_id' => $member->id,
                    'old_ip' => $member->last_payment_ip,
                    'new_ip' => $request->ip(),
                    'old_country' => $member->last_payment_country,
                    'route' => $request->route()?->getName(),
                ]);
            }
        }

        return $next($request);
    }
}
