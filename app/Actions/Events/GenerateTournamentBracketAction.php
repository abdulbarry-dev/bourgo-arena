<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\EventMatch;
use Illuminate\Support\Facades\DB;

class GenerateTournamentBracketAction
{
    public function execute(Event $event): void
    {
        DB::transaction(function () use ($event) {
            // Delete existing matches if any
            $event->matches()->delete();

            // Fetch checked-in participants, or registered if none checked in
            $participants = $event->participants()
                ->where('status', '!=', 'canceled')
                ->when($event->requires_check_in, fn ($q) => $q->where('has_checked_in', true))
                ->orderBy('seed_number')
                ->get();

            $participantCount = $participants->count();
            if ($participantCount < 2) {
                return; // Not enough participants
            }

            // Calculate bracket size (nearest power of 2)
            $bracketSize = pow(2, ceil(log($participantCount, 2)));
            $byesCount = $bracketSize - $participantCount;

            // Generate Match tree from final backwards to Round 1
            $totalRounds = log($bracketSize, 2);

            // We'll store matches by round to link them
            $matchesByRound = [];

            // Build the tree
            for ($round = $totalRounds; $round >= 1; $round--) {
                $matchesInRound = $bracketSize / pow(2, $round);
                $matchesByRound[$round] = [];

                for ($i = 0; $i < $matchesInRound; $i++) {
                    $nextMatchId = null;
                    if ($round < $totalRounds) {
                        $nextMatchIndex = (int) floor($i / 2);
                        $nextMatchId = $matchesByRound[$round + 1][$nextMatchIndex]->id;
                    }

                    $match = EventMatch::create([
                        'event_id' => $event->id,
                        'round' => $round,
                        'match_number' => $i + 1,
                        'status' => 'pending',
                        'next_match_id' => $nextMatchId,
                    ]);

                    $matchesByRound[$round][] = $match;
                }
            }

            // Assign participants to Round 1
            // In a real seeder, 1 plays 16, 2 plays 15, etc. For simplicity, we just pair them.
            $round1Matches = $matchesByRound[1];
            $participantIndex = 0;

            foreach ($round1Matches as $index => $match) {
                $p1 = $participants[$participantIndex++] ?? null;
                $p2 = null;

                // If we still have participants and haven't hit the byes limit for this pairing
                if ($participantIndex < $participantCount) {
                    // Distribute byes roughly
                    if ($byesCount > 0 && $index % 2 !== 0) {
                        $byesCount--;
                    } else {
                        $p2 = $participants[$participantIndex++] ?? null;
                    }
                }

                $match->update([
                    'participant1_id' => $p1?->id,
                    'participant2_id' => $p2?->id,
                ]);

                // If p2 is null (bye), p1 automatically wins and advances
                if ($p1 && ! $p2) {
                    $match->update([
                        'winner_id' => $p1->id,
                        'status' => 'completed',
                        'score' => 'BYE',
                    ]);

                    if ($match->next_match_id) {
                        $nextMatch = EventMatch::find($match->next_match_id);
                        if (! $nextMatch->participant1_id) {
                            $nextMatch->update(['participant1_id' => $p1->id]);
                        } else {
                            $nextMatch->update(['participant2_id' => $p1->id]);
                        }
                    }
                }
            }
        });
    }
}
