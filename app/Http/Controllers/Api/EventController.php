<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventMatchResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::query()
            ->withCount('participants')
            ->latest()
            ->paginate(15);

        return EventResource::collection($events);
    }

    public function show(Event $event)
    {
        $event->loadCount('participants');

        return new EventResource($event);
    }

    public function bracket(Event $event)
    {
        $matches = $event->matches()
            ->with(['participant1.user', 'participant2.user'])
            ->orderBy('round', 'asc')
            ->orderBy('match_number', 'asc')
            ->get()
            ->groupBy('round');

        $formattedBracket = [];
        foreach ($matches as $round => $roundMatches) {
            $formattedBracket['round_'.$round] = EventMatchResource::collection($roundMatches);
        }

        return response()->json([
            'data' => $formattedBracket,
        ]);
    }
}
