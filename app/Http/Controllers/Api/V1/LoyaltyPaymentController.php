<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreLoyaltyPaymentRequest;
use App\Http\Resources\Api\V1\LoyaltyPaymentResource;
use App\Models\Member;
use App\Models\Payment;
use App\Services\LoyaltyPaymentService;
use App\Services\LoyaltyService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyPaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected LoyaltyPaymentService $loyaltyPaymentService,
        protected LoyaltyService $loyaltyService,
    ) {}

    public function initiate(StoreLoyaltyPaymentRequest $request): JsonResponse
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            return response()->json([
                'success' => false,
                'error' => 'not_a_member',
                'message' => 'Loyalty payments are only available for members.',
            ], 403);
        }

        $payment = $this->loyaltyPaymentService->pay(
            $member,
            $request->validated('type'),
            (int) $request->validated('id'),
            $request,
        );

        $resource = new LoyaltyPaymentResource($payment->load(['reservation.activity', 'subscription.plan']));

        return $resource->response()->setStatusCode(201);
    }

    public function history(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->where('member_id', $request->user()->id)
            ->where('driver', 'loyalty')
            ->with(['reservation.activity', 'subscription.plan'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return LoyaltyPaymentResource::collection($payments)->response();
    }
}
