<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreReservationRequest;
use App\Http\Resources\Api\ApiReservationResource;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Services\LoyaltyCalculatorService;
use App\Services\ReservationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReservationService $reservationService,
        protected LoyaltyCalculatorService $loyaltyCalculatorService
    ) {}

    /**
     * Display a listing of the member's reservations.
     *
     * @return AnonymousResourceCollection<ApiReservationResource>
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $reservations = $request->user()->reservations()
            ->with(['activity', 'slot'])
            ->orderBy('date', 'desc')
            ->paginate(10);

        return $this->paginated($reservations, ApiReservationResource::class);
    }

    /**
     * Store a new reservation.
     *
     * @return ApiReservationResource
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $slot = ActivitySlot::query()->findOrFail($validated['activity_slot_id']);

        // Check if already booked for same slot by this member
        $exists = ApiReservation::where('member_id', $request->user()->id)
            ->where('activity_slot_id', $validated['activity_slot_id'])
            ->where('date', $slot->date)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'activity_slot_id' => ['You already have an active reservation for this slot.'],
            ]);
        }

        $reservation = $this->reservationService->makeActivityReservation($request->user(), $validated);
        $this->loyaltyCalculatorService->creditVariableForReservation($reservation);

        return (new ApiReservationResource($reservation->load(['activity', 'slot'])))->additional([
            'success' => true,
            'message' => 'Reservation created successfully',
        ])->response()->setStatusCode(201);
    }

    /**
     * Cancel a reservation.
     */
    public function destroy(ApiReservation $reservation): JsonResponse
    {
        $this->authorize('delete', $reservation);

        $this->reservationService->cancelActivityReservation($reservation);

        return $this->success(null, 'Reservation cancelled successfully');
    }
}
