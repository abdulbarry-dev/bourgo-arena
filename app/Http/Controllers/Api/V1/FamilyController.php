<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\FamilyChildDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AddChildRequest;
use App\Http\Requests\Api\V1\BookChildSessionRequest;
use App\Http\Requests\Api\V1\BuyChildSubscriptionRequest;
use App\Http\Requests\Api\V1\UpdateChildRequest;
use App\Http\Resources\Api\ApiReservationResource;
use App\Http\Resources\Api\V1\BookingResource;
use App\Http\Resources\Api\V1\ChildProfileResource;
use App\Http\Resources\Api\V1\CourseSessionResource;
use App\Http\Resources\Api\V1\MemberResource;
use App\Http\Resources\Api\V1\SubscriptionResource;
use App\Models\Booking;
use App\Models\CourseSession;
use App\Models\Member;
use App\Models\Plan;
use App\Services\ApiFamilyService;
use App\Services\FamilyChildService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class FamilyController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ApiFamilyService $familyService,
        protected FamilyChildService $familyChildService
    ) {}

    /**
     * Return authenticated member's children.
     *
     * @return AnonymousResourceCollection<MemberResource>
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $children = collect($request->user()->children);

        return MemberResource::collection($children)->additional([
            'success' => true,
            'message' => null,
        ]);
    }

    /**
     * Create a new child Member record.
     */
    public function store(AddChildRequest $request): JsonResponse
    {
        $parent = $request->user();

        if (! $parent instanceof Member) {
            abort(403, __('Forbidden'));
        }

        $dto = FamilyChildDTO::fromRequest($request);

        $child = $this->familyService->createChild($parent, $dto);

        return (new MemberResource($child))->additional([
            'success' => true,
            'message' => 'Child added successfully',
        ])->response()->setStatusCode(201);
    }

    /**
     * Update an existing child member.
     */
    public function update(UpdateChildRequest $request, Member $member): JsonResponse
    {
        $parent = $request->user();

        if (! $parent instanceof Member) {
            abort(403, __('Forbidden'));
        }

        $dto = FamilyChildDTO::fromRequest($request);

        $child = $this->familyService->updateChild($parent, $member, $dto);

        if ($child === null) {
            return $this->error('Unauthorized', 403);
        }

        return (new MemberResource($child))->additional([
            'success' => true,
            'message' => 'Child updated successfully',
        ])->response();
    }

    /**
     * Enable the family account feature.
     */
    public function enableFamilyFeature(Request $request): JsonResponse
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            abort(403, __('Forbidden'));
        }

        if ($member->is_family_account) {
            return $this->error('Family account feature already enabled', 400);
        }

        $member = $this->familyService->enableFamilyAccount($member);

        return (new MemberResource($member))->additional([
            'success' => true,
            'message' => 'Family account feature enabled successfully',
        ])->response();
    }

    /**
     * Disable the family account feature.
     */
    public function disableFamilyFeature(Request $request): JsonResponse
    {
        $member = $request->user();

        if (! $member instanceof Member) {
            abort(403, __('Forbidden'));
        }

        if (! $member->is_family_account) {
            return $this->error('Not a family account', 400);
        }

        $this->familyService->disableFamilyAccount($member);

        return $this->success(null, 'Family account feature disabled and children archived successfully');
    }

    /**
     * Delete a child member.
     */
    public function destroy(Request $request, Member $member): JsonResponse
    {
        $parent = $request->user();

        if (! $parent instanceof Member) {
            abort(403, __('Forbidden'));
        }

        if (! $this->familyService->deleteChild($parent, $member)) {
            return $this->error('Unauthorized', 403);
        }

        return $this->success(null, 'Child removed successfully');
    }

    /**
     * Get a child's profile with subscription data.
     */
    public function childProfile(Request $request, Member $member): JsonResponse
    {
        $parent = $this->getParentFromRequest($request);

        $child = $this->familyChildService->getChildProfile($parent, $member);

        return $this->success(new ChildProfileResource($child));
    }

    /**
     * Buy a subscription plan for a child.
     */
    public function buyChildSubscription(BuyChildSubscriptionRequest $request, Member $member): JsonResponse
    {
        $parent = $this->getParentFromRequest($request);

        $plan = Plan::findOrFail($request->integer('plan_id'));

        try {
            $subscription = $this->familyChildService->buySubscription(
                $parent,
                $member,
                $plan,
                $request->input('starts_at')
            );

            return $this->success(
                new SubscriptionResource($subscription),
                __('Subscription initiated successfully for your child. Please proceed to payment.'),
                201
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (AccessDeniedHttpException $e) {
            return $this->error($e->getMessage(), 403);
        }
    }

    /**
     * List a child's subscriptions.
     */
    public function childSubscriptions(Request $request, Member $member): AnonymousResourceCollection
    {
        $parent = $this->getParentFromRequest($request);

        $subscriptions = $this->familyChildService->getChildSubscriptions(
            $parent,
            $member,
            $request->integer('per_page', 15)
        );

        return $this->paginated($subscriptions, SubscriptionResource::class);
    }

    /**
     * List a child's course bookings.
     */
    public function childBookings(Request $request, Member $member): AnonymousResourceCollection
    {
        $parent = $this->getParentFromRequest($request);

        $bookings = $this->familyChildService->getChildBookings(
            $parent,
            $member,
            $request->query('filter'),
            $request->integer('per_page', 15)
        );

        return $this->paginated($bookings, BookingResource::class);
    }

    /**
     * List available course sessions for a child.
     */
    public function childAvailableSessions(Request $request, Member $member): AnonymousResourceCollection
    {
        $parent = $this->getParentFromRequest($request);

        $sessions = $this->familyChildService->getChildAvailableSessions(
            $parent,
            $member,
            $request->integer('per_page', 15)
        );

        return $this->paginated($sessions, CourseSessionResource::class);
    }

    /**
     * Book a course session on behalf of a child.
     */
    public function bookForChild(BookChildSessionRequest $request, Member $member, CourseSession $session): JsonResponse
    {
        $parent = $this->getParentFromRequest($request);

        try {
            $booking = $this->familyChildService->bookSessionForChild(
                $parent,
                $member,
                $session,
                $request->validated('date')
            );

            $booking->load('courseSession.course');

            return $this->success(
                new BookingResource($booking),
                __('Successfully enrolled your child in the session.'),
                201
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (AccessDeniedHttpException $e) {
            return $this->error($e->getMessage(), 403);
        }
    }

    /**
     * List a child's activity reservations.
     */
    public function childReservations(Request $request, Member $member): AnonymousResourceCollection
    {
        $parent = $this->getParentFromRequest($request);

        $reservations = $this->familyChildService->getChildReservations(
            $parent,
            $member,
            $request->query('filter'),
            $request->integer('per_page', 10)
        );

        return $this->paginated($reservations, ApiReservationResource::class);
    }

    /**
     * Get a child's combined schedule (bookings + reservations).
     */
    public function childSchedule(Request $request, Member $member): JsonResponse
    {
        $parent = $this->getParentFromRequest($request);

        $schedule = $this->familyChildService->getChildSchedule(
            $parent,
            $member,
            $request->query('from'),
            $request->query('to')
        );

        return $this->success($schedule);
    }

    /**
     * Mark a booking as completed (attended).
     */
    public function completeChildBooking(Request $request, Member $member, Booking $booking): JsonResponse
    {
        $parent = $this->getParentFromRequest($request);

        try {
            $updatedBooking = $this->familyChildService->completeChildBooking(
                $parent,
                $member,
                $booking
            );

            $updatedBooking->load('courseSession.course');

            return $this->success(
                new BookingResource($updatedBooking),
                __('Booking marked as completed.')
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (AccessDeniedHttpException $e) {
            return $this->error($e->getMessage(), 403);
        }
    }

    /**
     * Get a child's completed bookings and reservations.
     */
    public function childCompleted(Request $request, Member $member): JsonResponse
    {
        $parent = $this->getParentFromRequest($request);

        $completed = $this->familyChildService->getChildCompleted(
            $parent,
            $member,
            $request->integer('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'message' => null,
            'data' => $completed->items(),
            'meta' => [
                'current_page' => $completed->currentPage(),
                'last_page' => $completed->lastPage(),
                'per_page' => $completed->perPage(),
                'total' => $completed->total(),
            ],
        ]);
    }

    /**
     * Extract the authenticated parent Member from the request.
     */
    private function getParentFromRequest(Request $request): Member
    {
        $parent = $request->user();

        if (! $parent instanceof Member) {
            abort(403, __('Forbidden'));
        }

        return $parent;
    }
}
