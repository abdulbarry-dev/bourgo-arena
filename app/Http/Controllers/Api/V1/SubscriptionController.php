<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SubscriptionResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ApiResponse;

    /**
     * Return the authenticated member's active subscription with the plan loaded.
     */
    public function active(Request $request): SubscriptionResource|JsonResponse
    {
        $subscription = $request->user()->activeSubscription()->with('plan')->first();

        if (! $subscription) {
            return $this->success(null, 'No active subscription');
        }

        return (new SubscriptionResource($subscription))->additional([
            'success' => true,
            'message' => null,
        ]);
    }
}
