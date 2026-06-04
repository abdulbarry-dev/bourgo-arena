<?php

namespace App\Livewire\Admin\Events;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Team;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class EventParticipants extends Component
{
    use WithPagination;

    public Event $event;

    public $search = '';

    public $statusFilter = '';

    public $teamFilter = '';

    public ?int $viewingParticipantId = null;

    public ?EventParticipant $viewingParticipant = null;

    public bool $showDetailsFlyout = false;

    public ?int $removingParticipantId = null;

    public function mount(Event $event)
    {
        $this->event = $event;
    }

    public function openAddParticipantModal()
    {
        // Implementation for adding participants
        $this->dispatch('toast', message: __('Add participant functionality coming soon.'), type: 'info');
    }

    public function openDetails(int $id)
    {
        $this->viewingParticipant = EventParticipant::with(['user', 'team', 'event'])->findOrFail($id);
        $this->viewingParticipantId = $id;
        $this->showDetailsFlyout = true;

        Flux::modal('participant-details-modal')->show();
    }

    public function closeDetails()
    {
        $this->viewingParticipant = null;
        $this->viewingParticipantId = null;
        $this->showDetailsFlyout = false;
    }

    public function checkIn(int $id)
    {
        $participant = EventParticipant::findOrFail($id);
        $participant->update([
            'status' => 'checked_in',
            'has_checked_in' => true,
            'checked_in_at' => now(),
        ]);

        $this->dispatch('toast', message: __('Participant checked in successfully.'), type: 'success');
    }

    public function confirmRemoval(int $id)
    {
        $this->removingParticipantId = $id;
        // In a real app, this would show a confirmation modal.
        // For now, let's implement the removal directly or dispatch a toast.
        $this->dispatch('toast', message: __('Confirm removal for participant #:id', ['id' => $id]), type: 'warning');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedTeamFilter()
    {
        $this->resetPage();
    }

    #[Computed]
    public function availableTeams()
    {
        return Team::orderBy('name')->get();
    }

    public function render()
    {
        $participants = EventParticipant::query()
            ->where('event_id', $this->event->id)
            ->with(['user', 'team'])
            ->when($this->search, function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->teamFilter, function ($query) {
                $query->where('team_id', $this->teamFilter);
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.events.event-participants', [
            'participants' => $participants,
        ]);
    }
}
