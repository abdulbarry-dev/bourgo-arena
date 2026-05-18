<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LoyaltyPointResource;
use App\Models\LoyaltyPoint;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    use ApiResponse;

    public function balance(Request $request): JsonResponse
    {
        $member = $request->user();

        $limit = (int) config('loyalty.balance_history_limit', 20);

        $transactions = LoyaltyPoint::query()
            ->where('member_id', $member->id)
            ->latest('created_at')
            ->limit(max(1, $limit))
            ->get();

        return $this->success([
            'points' => (int) ($member->loyalty_points ?? 0),
            'transactions' => LoyaltyPointResource::collection($transactions),
        ]);
    }
}
