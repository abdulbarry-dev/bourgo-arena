<?php

namespace App\Http\Controllers\Api;

use App\Actions\Terminals\ProcessTerminalCheckInAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTerminalCheckInRequest;
use App\Http\Resources\Api\TerminalCheckInResource;
use App\Models\HikvisionTerminal;
use App\Services\TerminalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TerminalCheckInController extends Controller
{
    public function store(StoreTerminalCheckInRequest $request, ProcessTerminalCheckInAction $action): JsonResponse
    {
        /** @var HikvisionTerminal $terminal */
        $terminal = $request->attributes->get('terminal');

        $event = $action->execute($terminal, $request->validated());

        return $this->success(new TerminalCheckInResource($event), 'Check-in event received', 200);
    }

    public function heartbeat(Request $request, HikvisionTerminal $terminal, TerminalService $terminalService): JsonResponse
    {
        $terminalAuth = $request->attributes->get('terminal');

        $terminalService->assertAuthorizedForTerminal($terminalAuth, $terminal);

        $terminalService->heartbeat($terminal);

        return $this->success(null, 'Heartbeat received');
    }
}
