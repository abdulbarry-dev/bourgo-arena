<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Admin\CheckInEventResource;
use App\Models\CheckInEvent;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    /**
     * Display a listing of check-in events.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CheckInEvent::query()
            ->with(['member', 'terminal'])
            ->orderBy('created_at', 'desc');

        // Filtering
        if ($request->filled('member_id')) {
            $query->where('member_id', $request->input('member_id'));
        }

        if ($request->filled('result')) {
            $query->where('result', $request->input('result'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('checked_in_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('checked_in_at', '<=', $request->input('date_to'));
        }

        if ($request->has('is_suspicious')) {
            $query->where('is_suspicious', $request->boolean('is_suspicious'));
        }

        $events = $query->paginate($request->input('per_page', 50));

        return $this->success(CheckInEventResource::collection($events));
    }

    /**
     * Display check-in events for a specific member.
     */
    public function memberLogs(Member $member, Request $request): JsonResponse
    {
        $events = $member->checkInEvents()
            ->with('terminal')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return $this->success(CheckInEventResource::collection($events));
    }
}
