<?php

namespace App\Http\Controllers\Api\Member;

use App\DTOs\StoreReservationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreReservationRequest;
use App\Http\Resources\Api\ApiReservationResource;
use App\Repositories\Members\MemberRepository;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberReservationController extends Controller
{
    public function index(Request $request, MemberRepository $repository): JsonResponse
    {
        $reservations = $repository->getReservationsPaginated($request->user());

        return $this->paginated($reservations, ApiReservationResource::class);
    }

    public function store(StoreReservationRequest $request, ReservationService $reservationService): JsonResponse
    {
        $member = $request->user();
        $dto = StoreReservationDTO::fromRequest($request->validated());

        $reservation = $reservationService->makeActivityReservation($member, $dto);

        return $this->success(
            new ApiReservationResource($reservation->load(['activity', 'slot'])),
            __('Reservation created successfully.'),
            201
        );
    }

    public function cancel(Request $request, int $id, ReservationService $reservationService): JsonResponse
    {
        $reservation = $request->user()->reservations()->findOrFail($id);

        if ($reservation->payment_status === 'paid') {
            return $this->error(__('Paid reservations cannot be cancelled. Please contact an administrator.'), 403);
        }

        $reservationService->cancelActivityReservation($reservation);

        return $this->success(
            new ApiReservationResource($reservation->load(['activity', 'slot'])),
            __('Reservation cancelled successfully.')
        );
    }
}
