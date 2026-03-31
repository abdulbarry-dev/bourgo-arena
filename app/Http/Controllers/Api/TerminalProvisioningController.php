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
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TerminalProvisioningController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', HikvisionTerminal::class);

        $terminals = HikvisionTerminal::query()
            ->orderBy('id', 'desc')
            ->get();

        return HikvisionTerminalResource::collection($terminals);
    }

    public function store(StoreTerminalProvisioningRequest $request, ProvisionTerminalAction $action): JsonResponse
    {
        $terminal = $action->execute($request->validated());

        return response()->json([
            'message' => 'Terminal provisioned successfully',
            'data' => new HikvisionTerminalResource($terminal),
            'api_token' => $terminal->api_token, // explicitly keep api_token as it's required in test response directly
        ], 201);
    }

    public function revokeToken(Request $request, HikvisionTerminal $terminal, RevokeTerminalTokenAction $action): JsonResponse
    {
        $this->authorize('revokeToken', [HikvisionTerminal::class, $terminal]);

        $terminal = $action->execute($terminal);

        return response()->json([
            'message' => 'Terminal token revoked successfully',
            'api_token' => $terminal->api_token,
        ]);
    }

    public function decommission(Request $request, HikvisionTerminal $terminal, DecommissionTerminalAction $action): JsonResponse
    {
        $this->authorize('decommission', [HikvisionTerminal::class, $terminal]);

        $action->execute($terminal);

        return response()->json([
            'message' => 'Terminal decommissioned successfully',
        ]);
    }
}
