<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HikvisionTerminal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TerminalProvisioningController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', HikvisionTerminal::class);

        $terminals = HikvisionTerminal::query()
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'data' => $terminals,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('provision', HikvisionTerminal::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'serial_number' => ['required', 'string', 'max:255', 'unique:hikvision_terminals,serial_number'],
            'location' => ['required', 'string', 'max:255'],
            'terminal_type' => ['required', 'in:entry,exit'],
        ]);

        $validated['api_token'] = $this->generateUniqueToken();
        $validated['status'] = 'offline';

        $terminal = HikvisionTerminal::query()->create($validated);

        return response()->json([
            'message' => 'Terminal provisioned successfully',
            'data' => $terminal,
            'api_token' => $terminal->api_token,
        ], 201);
    }

    public function revokeToken(Request $request, HikvisionTerminal $terminal): JsonResponse
    {
        $this->authorize('revokeToken', HikvisionTerminal::class);

        $terminal->update([
            'api_token' => $this->generateUniqueToken(),
            'status' => 'offline',
            'last_seen_at' => null,
        ]);

        return response()->json([
            'message' => 'Terminal token revoked successfully',
            'api_token' => $terminal->api_token,
        ]);
    }

    public function decommission(Request $request, HikvisionTerminal $terminal): JsonResponse
    {
        $this->authorize('decommission', HikvisionTerminal::class);

        $terminal->decommission();

        return response()->json([
            'message' => 'Terminal decommissioned successfully',
        ]);
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (HikvisionTerminal::query()->where('api_token', $token)->exists());

        return $token;
    }
}
