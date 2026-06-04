<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PlanResource;
use App\Models\Plan;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlanController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of available plans.
     *
     * @return AnonymousResourceCollection<PlanResource>
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $plans = Plan::with('service')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($plans, PlanResource::class);
    }

    /**
     * Display the specified plan.
     */
    public function show(Plan $plan): PlanResource
    {
        $plan->load('service');

        return new PlanResource($plan);
    }
}
