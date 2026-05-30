<?php

namespace Database\Seeders\Dashboard\Events;

use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventParticipant;
use Illuminate\Database\Seeder;

class EventMatchSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::query()->where('name', 'Summer Padel Cup')->first();

        if ($event === null) {
            return;
        }

        $participants = EventParticipant::query()
            ->where('event_id', $event->id)
            ->where('status', 'approved')
            ->orderBy('seed_number')
            ->get();

        if ($participants->count() < 4) {
            return;
        }

        $semiFinalOne = EventMatch::query()->updateOrCreate(
            ['event_id' => $event->id, 'round' => 1, 'match_number' => 1],
            [
                'participant1_id' => $participants[0]->id,
                'participant2_id' => $participants[3]->id,
                'scheduled_at' => now()->addDays(12),
                'winner_id' => $participants[0]->id,
                'score' => '6-4, 6-3',
                'status' => 'completed',
                'next_match_id' => null,
            ],
        );

        EventMatch::query()->updateOrCreate(
            ['event_id' => $event->id, 'round' => 1, 'match_number' => 2],
            [
                'participant1_id' => $participants[1]->id,
                'participant2_id' => $participants[2]->id,
                'scheduled_at' => now()->addDays(12)->addHours(2),
                'winner_id' => $participants[1]->id,
                'score' => '7-5, 6-2',
                'status' => 'completed',
                'next_match_id' => null,
            ],
        );

        EventMatch::query()->updateOrCreate(
            ['event_id' => $event->id, 'round' => 2, 'match_number' => 1],
            [
                'participant1_id' => $participants[0]->id,
                'participant2_id' => $participants[1]->id,
                'scheduled_at' => now()->addDays(14),
                'winner_id' => $participants[0]->id,
                'score' => '6-2, 3-6, 10-8',
                'status' => 'scheduled',
                'next_match_id' => $semiFinalOne->id,
            ],
        );
    }
}
