<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ApiReservationResource;
use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberReservationController extends Controller
{
    public function index(): JsonResponse
    {
        $reservations = auth()->user()->reservations()
            ->with(['activity', 'slot'])
            ->latest()
            ->paginate();

        return $this->paginated($reservations, ApiReservationResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'activity_id' => ['required', 'exists:activities,id'],
            'activity_slot_id' => ['required', 'exists:activity_slots,id'],
        ]);

        $activity = Activity::findOrFail($request->activity_id);
        $slot = ActivitySlot::findOrFail($request->activity_slot_id);

        if ($slot->isFullyBooked()) {
            return $this->error(__('This slot is already fully booked.'), 422);
        }

        if (! $slot->is_available) {
            return $this->error(__('This slot is not available for booking.'), 422);
        }

        $reservation = DB::transaction(function () use ($activity, $slot) {
            $reservation = ApiReservation::create([
                'member_id' => auth()->id(),
                'activity_id' => $activity->id,
                'activity_slot_id' => $slot->id,
                'date' => $slot->date,
                'starts_at' => $slot->starts_at,
                'ends_at' => $slot->ends_at,
                'price' => $activity->base_price,
                'status' => 'confirmed',
                'payment_status' => 'pending',
            ]);

            $slot->increment('booked_count');

            return $reservation;
        });

        return $this->success(
            new ApiReservationResource($reservation->load(['activity', 'slot'])),
            __('Reservation created successfully.'),
            201
        );
    }

    public function cancel(int $id): JsonResponse
    {
        $reservation = auth()->user()->reservations()->findOrFail($id);

        if ($reservation->status === 'cancelled') {
            return $this->error(__('Reservation is already cancelled.'), 422);
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            if ($reservation->slot) {
                $reservation->slot->decrement('booked_count');
            }
        });

        return $this->success(
            new ApiReservationResource($reservation->load(['activity', 'slot'])),
            __('Reservation cancelled successfully.')
        );
    }
}
