<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SubscriptionResource;
use App\Services\SubscriptionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ApiResponse;

    /**
     * Return the authenticated member's active subscription with the plan loaded.
     */
    public function active(Request $request, SubscriptionService $service): SubscriptionResource|JsonResponse
    {
        $subscription = $service->getActiveForUser($request->user());

        if (! $subscription) {
            return $this->success(null, 'No active subscription');
        }

        return (new SubscriptionResource($subscription))->additional(['success' => true, 'message' => null]);
    }

    /**
     * Subscribe to a plan.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        // TODO: Implement actual subscription creation and payment initiation flow
        // Return a dummy response for now to align the API endpoint structure
        return $this->success(null, 'Subscription initiated successfully', 201);
    }
}
