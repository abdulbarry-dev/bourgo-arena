<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Admin\AdminAlertResource;
use App\Models\AdminAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAlertController extends Controller
{
    /**
     * Display a listing of active admin alerts.
     */
    public function index(Request $request): JsonResponse
    {
        $alerts = AdminAlert::query()
            ->with(['terminal', 'member'])
            ->where('is_dismissed', false)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return $this->success(AdminAlertResource::collection($alerts));
    }

    /**
     * Mark an alert as dismissed.
     */
    public function dismiss(AdminAlert $alert): JsonResponse
    {
        $alert->update(['is_dismissed' => true]);

        return $this->success(new AdminAlertResource($alert), __('Alert dismissed successfully.'));
    }

    /**
     * Escalate an alert.
     */
    public function escalate(AdminAlert $alert): JsonResponse
    {
        // Placeholder for escalation logic (e.g., notifying a manager or creating a ticket)
        return $this->success(new AdminAlertResource($alert), __('Alert escalated to management.'));
    }
}
