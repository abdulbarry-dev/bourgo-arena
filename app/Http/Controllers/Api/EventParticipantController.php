<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventParticipantResource;
use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventParticipantController extends Controller
{
    public function myEvents(Request $request)
    {
        $participants = EventParticipant::with('event')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return EventParticipantResource::collection($participants);
    }

    public function register(Request $request, Event $event)
    {
        if ($event->status !== 'open') {
            return response()->json(['message' => 'Event is not open for registration'], 422);
        }

        $existing = EventParticipant::where('event_id', $event->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Already registered'], 422);
        }

        $currentCount = $event->participants()->count();
        $status = $currentCount >= $event->max_participants ? 'waitlisted' : 'approved';

        $participant = EventParticipant::create([
            'event_id' => $event->id,
            'user_id' => $request->user()->id,
            'status' => $status,
        ]);

        return response()->json([
            'message' => 'Successfully registered',
            'status' => $status,
            'data' => new EventParticipantResource($participant->load('user')),
        ], 201);
    }

    public function withdraw(Request $request, Event $event)
    {
        $participant = EventParticipant::where('event_id', $event->id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($participant->status === 'withdrawn') {
            return response()->json(['message' => 'Already withdrawn'], 422);
        }

        DB::transaction(function () use ($participant, $event) {
            $participant->update([
                'status' => 'withdrawn',
                'withdrawn_at' => now(),
            ]);

            // If the bracket is active, grant a walkover to opponents
            if ($event->status === 'in_progress') {
                $activeMatch = $event->matches()
                    ->where('status', 'scheduled')
                    ->where(function ($q) use ($participant) {
                        $q->where('participant1_id', $participant->id)
                            ->orWhere('participant2_id', $participant->id);
                    })->first();

                if ($activeMatch) {
                    $winnerId = $activeMatch->participant1_id == $participant->id
                                ? $activeMatch->participant2_id
                                : $activeMatch->participant1_id;

                    $activeMatch->update([
                        'winner_id' => $winnerId,
                        'status' => 'walkover',
                    ]);

                    if ($activeMatch->next_match_id) {
                        $nextMatch = $event->matches()->find($activeMatch->next_match_id);
                        if ($activeMatch->match_number % 2 != 0) {
                            $nextMatch->update(['participant1_id' => $winnerId]);
                        } else {
                            $nextMatch->update(['participant2_id' => $winnerId]);
                        }
                    }
                }
            }
            // If tournament hasn't started, try to promote a waitlisted user
            elseif ($event->status === 'open' || $event->status === 'draft') {
                $waitlisted = EventParticipant::where('event_id', $event->id)
                    ->where('status', 'waitlisted')
                    ->oldest()
                    ->first();

                if ($waitlisted) {
                    $waitlisted->update(['status' => 'pending']); // or approved
                }
            }
        });

        return response()->json(['message' => 'Successfully withdrawn']);
    }

    public function checkIn(Request $request, Event $event)
    {
        if (! $event->requires_check_in) {
            return response()->json(['message' => 'This event does not require check-in'], 422);
        }

        if (! now()->isSameDay($event->start_date)) {
            return response()->json(['message' => 'Check-in is only available on the day of the event'], 422);
        }

        $participant = EventParticipant::where('event_id', $event->id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $participant->update(['has_checked_in' => true]);

        return response()->json(['message' => 'Successfully checked in']);
    }
}
