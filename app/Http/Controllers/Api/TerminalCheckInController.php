<?php

namespace App\Http\Controllers\Api;

use App\Actions\Terminals\ProcessTerminalCheckInAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTerminalCheckInRequest;
use App\Http\Resources\Api\TerminalCheckInResource;
use App\Models\HikvisionTerminal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TerminalCheckInController extends Controller
{
    public function store(StoreTerminalCheckInRequest $request, ProcessTerminalCheckInAction $action): JsonResponse
    {
        /** @var HikvisionTerminal $terminal */
        $terminal = $request->attributes->get('terminal');

        $event = $action->execute($terminal, $request->validated());

        return response()->json([
            'message' => 'Check-in event received',
            'data' => new TerminalCheckInResource($event),
        ]);
    }

    public function heartbeat(Request $request, HikvisionTerminal $terminal): JsonResponse
    {
        // terminal.auth already verifies the token and matches it to a device.
        $terminalAuth = $request->attributes->get('terminal');

        // Optional: Ensure the authenticated terminal matches the requested endpoint id
        if ($terminalAuth && $terminalAuth->id !== $terminal->id) {
            return response()->json(['message' => 'Unauthorized for this terminal'], 403);
        }

        $terminal->markSeen();

        return response()->json([
            'message' => 'Heartbeat received',
        ]);
    }
}
