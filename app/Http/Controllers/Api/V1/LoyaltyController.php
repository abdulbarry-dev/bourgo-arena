<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LoyaltyPointResource;
use App\Services\LoyaltyService;
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

        $result = app(LoyaltyService::class)->getBalanceAndTransactions($member, $limit);

        return $this->success([
            'points' => $result['points'],
            'transactions' => LoyaltyPointResource::collection($result['transactions']),
        ]);
    }
}
