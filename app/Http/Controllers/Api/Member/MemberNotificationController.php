<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberNotificationResource;
use App\Services\Members\AuthenticatedMemberResolver;
use App\Services\Members\MemberNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MemberNotificationController extends Controller
{
    public function __construct(
        private readonly AuthenticatedMemberResolver $authenticatedMemberResolver,
        private readonly MemberNotificationService $memberNotificationService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $member = $this->authenticatedMemberResolver->resolve($request);

        $notifications = $this->memberNotificationService->paginatedForMember($member);

        return MemberNotificationResource::collection($notifications);
    }
}
