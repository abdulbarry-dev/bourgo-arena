<?php

namespace App\Actions\Events;

use App\Models\EventMatch;

class AdvanceMatchWinnerAction
{
    /**
     * Advance the winner of a match to the next match in the bracket.
     */
    public function execute(EventMatch $match, int $winnerParticipantId, ?string $score = null): void
    {
        if ($match->status === 'completed') {
            throw new \Exception('Match is already completed.');
        }

        // Validate the winner is actually a participant in this match
        if ($match->participant1_id !== $winnerParticipantId && $match->participant2_id !== $winnerParticipantId) {
            throw new \Exception('The specified winner is not a participant in this match.');
        }

        // Update current match
        $match->update([
            'winner_id' => $winnerParticipantId,
            'status' => 'completed',
            'score' => $score,
        ]);

        // Advance winner to the next match if there is one
        if ($match->next_match_id) {
            $nextMatch = EventMatch::findOrFail($match->next_match_id);

            // Determine which slot to place the winner in
            // Typically, if this is an "even" match number from the top, they go to participant1,
            // but for simplicity, we just fill the first empty slot.
            if (! $nextMatch->participant1_id) {
                $nextMatch->update(['participant1_id' => $winnerParticipantId]);
            } elseif (! $nextMatch->participant2_id) {
                $nextMatch->update(['participant2_id' => $winnerParticipantId]);
            } else {
                throw new \Exception('Next match is already full.');
            }
        }
    }
}
