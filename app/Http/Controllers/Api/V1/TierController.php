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

    /**
     * Display a listing of all available membership tiers.
     */
    public function index(): JsonResponse
    {
        return $this->success([
            'tiers' => [
                [
                    'label' => 'Standard',
                    'multiplier' => 1.0,
                    'requirements' => __('Default membership tier.'),
                    'benefits' => __('Basic access to arena facilities.')
                ],
                [
                    'label' => 'Plus',
                    'multiplier' => 1.2,
                    'requirements' => __('Requires 2 active subscriptions.'),
                    'benefits' => __('20% boost to loyalty points earning.')
                ],
                [
                    'label' => 'Ultra',
                    'multiplier' => 1.5,
                    'requirements' => __('Requires 3 active subscriptions.'),
                    'benefits' => __('50% boost to loyalty points earning.')
                ],
                [
                    'label' => 'Max',
                    'multiplier' => 2.0,
                    'requirements' => __('Requires 4 or more active subscriptions.'),
                    'benefits' => __('100% boost to loyalty points earning.')
                ],
            ],
            'family_tiers' => [
                [
                    'label' => 'Family',
                    'multiplier' => 1.0,
                    'requirements' => __('Default family membership.'),
                    'benefits' => __('Basic access for all linked family members.')
                ],
                [
                    'label' => 'Family Plus',
                    'multiplier' => 1.2,
                    'requirements' => __('Requires 2 active family subscriptions.'),
                    'benefits' => __('20% boost to family loyalty points.')
                ],
                [
                    'label' => 'Family Ultra',
                    'multiplier' => 1.5,
                    'requirements' => __('Requires 3 active family subscriptions.'),
                    'benefits' => __('50% boost to family loyalty points.')
                ],
                [
                    'label' => 'Family Max',
                    'multiplier' => 2.0,
                    'requirements' => __('Requires 4 or more active family subscriptions.'),
                    'benefits' => __('100% boost to family loyalty points.')
                ],
            ]
        ]);
    }

    /**
     * Display the current user's tier.
     */
    public function show(Request $request): JsonResponse
    {
        return $this->success([
            ...$this->tierResolutionService->resolveTier($request->user()),
        ]);
    }
}
