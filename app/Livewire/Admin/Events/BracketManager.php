<?php

namespace App\Livewire\Admin\Events;

use App\Models\Event;
use App\Models\EventMatch;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class BracketManager extends Component
{
    public Event $event;

    // Match Edit Modal
    public $isMatchModalOpen = false;

    public $editingMatchId = null;

    public $score = '';

    public $winner_id = null;

    public function mount(Event $event)
    {
        $this->event = $event;
    }

    public function generateBracket()
    {
        if ($this->event->matches()->count() > 0) {
            $this->addError('bracket', 'Bracket is already generated.');

            return;
        }

        // Get approved participants, sorted by seed_number
        $participants = $this->event->participants()
            ->where('status', 'approved')
            ->orderByRaw('ISNULL(seed_number), seed_number ASC')
            ->get();

        $count = $participants->count();
        if ($count < 2) {
            $this->addError('bracket', 'Not enough approved participants to generate a bracket.');

            return;
        }

        // Find next power of 2
        $power = 1;
        while ($power < $count) {
            $power *= 2;
        }
        $byesCount = $power - $count;

        // Simple seeding/shuffling algorithm
        $orderedParticipants = $participants->toArray(); // Array of participants

        // Pad with nulls for BYEs
        for ($i = 0; $i < $byesCount; $i++) {
            $orderedParticipants[] = null;
        }

        // Calculate total rounds
        $totalRounds = log($power, 2);

        DB::transaction(function () use ($totalRounds, $orderedParticipants) {
            $previousRoundMatches = [];

            // Build matches from Final backwards to Round 1 to easily set next_match_id
            for ($round = $totalRounds; $round >= 1; $round--) {
                $matchesInRound = pow(2, $totalRounds - $round);
                $currentRoundMatches = [];

                for ($m = 0; $m < $matchesInRound; $m++) {
                    $nextMatchId = null;
                    if ($round < $totalRounds) {
                        // Integer division determines parent match
                        $parentIndex = intdiv($m, 2);
                        $nextMatchId = $previousRoundMatches[$parentIndex]->id;
                    }

                    $match = EventMatch::create([
                        'event_id' => $this->event->id,
                        'round' => $round,
                        'match_number' => $m + 1,
                        'status' => 'scheduled',
                        'next_match_id' => $nextMatchId,
                    ]);

                    $currentRoundMatches[] = $match;
                }

                $previousRoundMatches = $currentRoundMatches;

                // If this is Round 1, populate participants
                if ($round == 1) {
                    foreach ($currentRoundMatches as $index => $match) {
                        $p1 = $orderedParticipants[$index * 2];
                        $p2 = $orderedParticipants[$index * 2 + 1];

                        $match->update([
                            'participant1_id' => $p1 ? $p1['id'] : null,
                            'participant2_id' => $p2 ? $p2['id'] : null,
                        ]);

                        // Auto advance if there is a BYE
                        if ($p1 && ! $p2) {
                            $this->advanceWinner($match, $p1['id'], 'walkover');
                        } elseif (! $p1 && $p2) {
                            $this->advanceWinner($match, $p2['id'], 'walkover');
                        }
                    }
                }
            }
        });

        $this->event->update(['status' => 'in_progress']);
    }

    public function editMatch(EventMatch $match)
    {
        $this->editingMatchId = $match->id;
        $this->score = $match->score;
        $this->winner_id = $match->winner_id;
        Flux::modal('match-result-modal')->show();
    }

    public function saveMatch()
    {
        $match = EventMatch::find($this->editingMatchId);
        if (! $match) {
            return;
        }

        $match->update([
            'score' => $this->score,
            'winner_id' => $this->winner_id,
            'status' => $this->winner_id ? 'completed' : 'ongoing',
        ]);

        if ($this->winner_id) {
            $this->advanceWinner($match, $this->winner_id, 'completed');
        }

        Flux::modal('match-result-modal')->close();
        $this->editingMatchId = null;
    }

    private function advanceWinner(EventMatch $match, $winnerId, $status)
    {
        $match->update([
            'winner_id' => $winnerId,
            'status' => $status,
        ]);

        if ($match->next_match_id) {
            $nextMatch = EventMatch::find($match->next_match_id);
            // Determine if winner goes to participant1 or participant2 slot
            // Since matches are paired consecutively 1-2, 3-4 -> Next round 1, 2
            if ($match->match_number % 2 != 0) {
                $nextMatch->update(['participant1_id' => $winnerId]);
            } else {
                $nextMatch->update(['participant2_id' => $winnerId]);
            }
        }
    }

    public function render()
    {
        $matchesByRound = $this->event->matches()
            ->with(['participant1.user', 'participant2.user', 'winner.user'])
            ->orderBy('round', 'asc')
            ->orderBy('match_number', 'asc')
            ->get()
            ->groupBy('round');

        return view('livewire.admin.events.bracket-manager', [
            'matchesByRound' => $matchesByRound,
            'hasBracket' => $this->event->matches()->count() > 0,
        ]);
    }
}
