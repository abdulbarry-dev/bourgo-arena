<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SubscriptionResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionController extends Controller
{
    use ApiResponse;

    /**
     * Return the authenticated member's active subscriptions with plans loaded.
     * Supports members with multiple concurrent active plans.
     */
    public function active(Request $request, SubscriptionService $service): JsonResponse
    {
        $subscriptions = $service->getActiveSubscriptionsForUser($request->user());

        if ($subscriptions->isEmpty()) {
            return $this->success([], __('No active subscriptions were found for your account.'));
        }

        $count = $subscriptions->count();
        $message = $count > 1
            ? __('Successfully retrieved :count active subscriptions detailing your current planning access.', ['count' => $count])
            : __('Your active subscription details have been retrieved successfully.');

        return $this->success(
            SubscriptionResource::collection($subscriptions),
            $message
        );
    }

    /**
     * Return the authenticated member's subscription history.
     *
     * @return AnonymousResourceCollection<SubscriptionResource>
     */
    public function history(Request $request): AnonymousResourceCollection
    {
        $subscriptions = $request->user()->subscriptions()
            ->with(['plan.service'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($subscriptions, SubscriptionResource::class);
    }

    /**
     * Subscribe to a plan.
     */
    public function store(Request $request, SubscriptionService $service): JsonResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'starts_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        $plan = Plan::findOrFail($request->integer('plan_id'));
        $member = $request->user();

        // Use service for validation
        $validationResult = $service->validateEnrollment($member, $plan);
        if ($validationResult !== true) {
            return $this->error($validationResult, 422);
        }

        // Create a pending subscription for self-service
        $subscription = $service->enroll($member, $plan, [
            'status' => 'pending',
            'starts_at' => $request->input('starts_at', now()->toDateString()),
            'payment_method' => 'konnect',
            // No enrolled_by as it's self-service
        ]);

        return $this->success(
            new SubscriptionResource($subscription),
            __('Subscription initiated successfully. Please proceed to payment.'),
            201
        );
    }

    /**
     * Cancel a pending subscription (abandon stuck payment flow).
     */
    public function cancel(Subscription $subscription, Request $request, SubscriptionService $service): JsonResponse
    {
        if ($subscription->member_id !== $request->user()->id) {
            return $this->error(__('Subscription not found.'), 404);
        }

        if ($subscription->status !== 'pending') {
            return $this->error(__('Only pending subscriptions can be cancelled.'), 422);
        }

        $service->cancelPending($subscription);

        return $this->success(
            new SubscriptionResource($subscription->fresh()),
            __('Pending subscription cancelled successfully.')
        );
    }
}
