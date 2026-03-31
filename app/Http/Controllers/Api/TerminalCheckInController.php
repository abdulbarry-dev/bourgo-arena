<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckInEvent;
use App\Models\HikvisionTerminal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TerminalCheckInController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        /** @var HikvisionTerminal $terminal */
        $terminal = $request->attributes->get('terminal');

        // Handle ISAPI JSON payload format from Hikvision
        if ($request->has('AccessControllerEvent')) {
            $isapiEvent = $request->input('AccessControllerEvent');
            $request->merge([
                'card_uid' => $isapiEvent['cardNo'] ?? null,
                'result' => ($isapiEvent['subEventType'] ?? null) === 75 ? 'authorized' : 'denied', // 75 is typical access granted
                'denial_reason' => ($isapiEvent['subEventType'] ?? null) !== 75 ? 'invalid_card' : null,
                'checked_in_at' => $request->input('dateTime') ?? now(),
            ]);
        }

        $validated = $request->validate([
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'card_uid' => ['required', 'string', 'max:255'],
            'result' => ['required', 'in:authorized,denied'],
            'denial_reason' => ['nullable', 'in:expired_subscription,suspended_card,invalid_card,anti_passback'],
            'is_suspicious' => ['nullable', 'boolean'],
            'checked_in_at' => ['nullable', 'date'],
        ]);

        $event = CheckInEvent::query()->create([
            'member_id' => $validated['member_id'] ?? null,
            'card_uid' => $validated['card_uid'],
            'terminal_id' => $terminal->id,
            'result' => $validated['result'],
            'denial_reason' => $validated['denial_reason'] ?? null,
            'is_suspicious' => $validated['is_suspicious'] ?? false,
            'checked_in_at' => $validated['checked_in_at'] ?? now(),
        ]);

        $terminal->markSeen();

        return response()->json([
            'message' => 'Check-in event received',
            'data' => [
                'event_id' => $event->id,
                'terminal_id' => $terminal->id,
            ],
        ]);
    }
}
