<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Admin\CheckInEventResource;
use App\Models\CheckInEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AdminCheckInController extends Controller
{
    /**
     * Display a live feed of the last 20 check-in events.
     */
    public function live(): JsonResponse
    {
        $events = CheckInEvent::query()
            ->with(['member', 'terminal'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return $this->success(CheckInEventResource::collection($events));
    }

    /**
     * Get current gym occupancy statistics.
     */
    public function occupancy(): JsonResponse
    {
        $dateStr = now()->toDateString();
        $occupancyKey = "gym:occupancy:{$dateStr}";

        $currentOccupancy = (int) Cache::get($occupancyKey, 0);
        $capacity = config('app.gym_capacity', 100); // Default to 100 if not set

        return $this->success([
            'current' => $currentOccupancy,
            'capacity' => $capacity,
            'percentage' => $capacity > 0 ? round(($currentOccupancy / $capacity) * 100, 2) : 0,
        ]);
    }
}
