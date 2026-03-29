<?php

namespace App\Http\Middleware;

use App\Models\HikvisionTerminal;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TerminalAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?: $request->header('X-Terminal-Token');

        if ($token === null || $token === '') {
            return $this->unauthorized();
        }

        $terminal = HikvisionTerminal::query()
            ->where('api_token', $token)
            ->where('status', '!=', 'decommissioned')
            ->first();

        if ($terminal === null) {
            return $this->unauthorized();
        }

        $request->attributes->set('terminal', $terminal);

        return $next($request);
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json([
            'message' => 'Unauthorized terminal',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
