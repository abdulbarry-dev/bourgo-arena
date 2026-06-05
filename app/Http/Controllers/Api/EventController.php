<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EventResource;
use App\Http\Resources\EventMatchResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::query()
            ->published()
            ->withCount('participants')
            ->latest()
            ->paginate(15);

        return EventResource::collection($events);
    }

    public function show(Event $event)
    {
        abort_if($event->status === 'draft', 404);

        $event->loadCount('participants');

        return new EventResource($event);
    }

    public function bracket(Event $event)
    {
        $cacheKey = "event.{$event->id}.bracket";

        $formattedBracket = Cache::remember($cacheKey, now()->addHours(24), function () use ($event) {
            $matches = $event->matches()
                ->with(['participant1.user', 'participant1.team', 'participant2.user', 'participant2.team'])
                ->orderBy('round', 'asc')
                ->orderBy('match_number', 'asc')
                ->get()
                ->groupBy('round');

            $bracket = [];
            foreach ($matches as $round => $roundMatches) {
                $bracket['round_'.$round] = EventMatchResource::collection($roundMatches);
            }

            return $bracket;
        });

        return response()->json([
            'data' => $formattedBracket,
        ]);
    }
}
