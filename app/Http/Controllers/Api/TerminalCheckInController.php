<?php

namespace App\Http\Controllers\Api;

use App\Actions\Terminals\ProcessTerminalCheckInAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTerminalCheckInRequest;
use App\Http\Resources\Api\TerminalCheckInResource;
use App\Models\HikvisionTerminal;
use Illuminate\Http\JsonResponse;

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
}
