<?php

namespace App\Livewire\Admin\Events;

use App\Actions\Events\AdvanceMatchWinnerAction;
use App\Actions\Events\GenerateTournamentBracketAction;
use App\Jobs\NotifyBracketPublishedJob;
use App\Models\Event;
use App\Models\EventMatch;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EventBracketManager extends Component
{
    public Event $event;

    public $bracketExists = false;

    // Advance match modal state
    public $advancingMatchId = null;

    public $winnerId = null;

    public $matchScore = '';

    public function mount(Event $event)
    {
        $this->event = $event;
        $this->checkBracketExists();
    }

    public function checkBracketExists()
    {
        $this->bracketExists = $this->event->matches()->exists();
    }

    public function generateBracket()
    {
        $action = new GenerateTournamentBracketAction;
        $action->execute($this->event);

        $this->checkBracketExists();
        Flux::toast('Bracket generated successfully.', variant: 'success');
    }

    public function publishBracket()
    {
        NotifyBracketPublishedJob::dispatch($this->event);
        Flux::toast('Bracket published! Participants have been notified.', variant: 'success');
    }

    public function openAdvanceModal($matchId)
    {
        $this->advancingMatchId = $matchId;
        $this->winnerId = null;
        $this->matchScore = '';
        Flux::modal('advance-match-modal')->show();
    }

    public function confirmAdvance()
    {
        $this->validate([
            'winnerId' => 'required|integer',
            'matchScore' => 'nullable|string|max:50',
        ]);

        $match = EventMatch::findOrFail($this->advancingMatchId);

        try {
            DB::transaction(function () use ($match) {
                $action = new AdvanceMatchWinnerAction;
                $action->execute($match, $this->winnerId, $this->matchScore);
            });
            Flux::toast('Match winner advanced successfully.', variant: 'success');
        } catch (\Exception $e) {
            Flux::toast($e->getMessage(), variant: 'danger');
        }

        Flux::modal('advance-match-modal')->close();
        $this->advancingMatchId = null;
        $this->winnerId = null;
        $this->matchScore = '';
    }

    public function render()
    {
        $matchesByRound = [];
        if ($this->bracketExists) {
            $matches = $this->event->matches()
                ->with(['participant1.user', 'participant2.user', 'participant1.team', 'participant2.team'])
                ->orderBy('round', 'asc')
                ->orderBy('match_number', 'asc')
                ->get();

            $matchesByRound = $matches->groupBy('round');
        }

        return view('livewire.admin.events.event-bracket-manager', [
            'matchesByRound' => $matchesByRound,
        ]);
    }
}
