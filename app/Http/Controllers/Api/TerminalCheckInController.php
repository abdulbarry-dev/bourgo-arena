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

        return $this->success(new TerminalCheckInResource($event), 'Check-in event received');
    }

    public function heartbeat(Request $request, HikvisionTerminal $terminal): JsonResponse
    {
        // terminal.auth already verifies the token and matches it to a device.
        $terminalAuth = $request->attributes->get('terminal');

        // Optional: Ensure the authenticated terminal matches the requested endpoint id
        if ($terminalAuth && $terminalAuth->id !== $terminal->id) {
            return $this->error('Unauthorized for this terminal', 403);
        }

        $terminal->markSeen();

        return $this->success(null, 'Heartbeat received');
    }
}
