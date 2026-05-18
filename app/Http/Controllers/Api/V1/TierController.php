<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TierResolutionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TierController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TierResolutionService $tierResolutionService
    ) {}

    public function show(Request $request): JsonResponse
    {
        return $this->success([
            ...$this->tierResolutionService->resolveTier($request->user()),
        ]);
    }
}
