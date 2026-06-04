<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\Api\V1\Tier\MemberTierResponse;
use App\Http\Responses\Api\V1\Tier\TierIndexResponse;
use App\Services\TierResolutionService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TierController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TierResolutionService $tierResolutionService
    ) {}

    /**
     * Display a listing of all available membership tiers.
     */
    public function index(): TierIndexResponse
    {
        return new TierIndexResponse(
            $this->tierResolutionService->getAllTiers()
        );
    }

    /**
     * Display the current user's tier.
     */
    public function show(Request $request): MemberTierResponse
    {
        return new MemberTierResponse(
            $this->tierResolutionService->resolveTier($request->user())
        );
    }
}
