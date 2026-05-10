<?php

namespace App\Http\Controllers\Api;

use App\Actions\Terminals\DecommissionTerminalAction;
use App\Actions\Terminals\ProvisionTerminalAction;
use App\Actions\Terminals\RevokeTerminalTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTerminalProvisioningRequest;
use App\Http\Resources\Api\HikvisionTerminalResource;
use App\Models\HikvisionTerminal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TerminalProvisioningController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', HikvisionTerminal::class);

        $terminals = HikvisionTerminal::query()
            ->orderBy('id', 'desc')
            ->get();

        return $this->success(HikvisionTerminalResource::collection($terminals));
    }

    public function store(StoreTerminalProvisioningRequest $request, ProvisionTerminalAction $action): JsonResponse
    {
        $terminal = $action->execute($request->validated());

        return $this->success([
            'terminal' => new HikvisionTerminalResource($terminal),
            'api_token' => $terminal->api_token,
        ], 'Terminal provisioned successfully', 201);
    }

    public function revokeToken(Request $request, HikvisionTerminal $terminal, RevokeTerminalTokenAction $action): JsonResponse
    {
        $this->authorize('revokeToken', [HikvisionTerminal::class, $terminal]);

        $terminal = $action->execute($terminal);

        return $this->success([
            'api_token' => $terminal->api_token,
        ], 'Terminal token revoked successfully');
    }

    public function decommission(Request $request, HikvisionTerminal $terminal, DecommissionTerminalAction $action): JsonResponse
    {
        $this->authorize('decommission', [HikvisionTerminal::class, $terminal]);

        $action->execute($terminal);

        return $this->success(null, 'Terminal decommissioned successfully');
    }
}
