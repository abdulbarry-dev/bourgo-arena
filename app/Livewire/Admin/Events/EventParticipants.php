<?php

namespace App\Livewire\Admin\Events;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Team;
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
    
    public ?int $editingParticipantId = null;
    public ?EventParticipant $editingParticipant = null;
    public $editingTeamId = null;
    public $editingSeedNumber = null;
    public $editingStatus = '';

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
        
        \Flux\Flux::modal('participant-details-modal')->show();
    }

    public function closeDetails()
    {
        $this->viewingParticipant = null;
        $this->viewingParticipantId = null;
    }

    public function edit(int $id)
    {
        $this->editingParticipant = EventParticipant::with('user')->findOrFail($id);
        $this->editingParticipantId = $id;
        $this->editingTeamId = $this->editingParticipant->team_id;
        $this->editingSeedNumber = $this->editingParticipant->seed_number;
        $this->editingStatus = $this->editingParticipant->status;

        \Flux\Flux::modal('edit-registration-modal')->show();
    }

    public function saveEdit()
    {
        $this->validate([
            'editingTeamId' => 'nullable|exists:teams,id',
            'editingSeedNumber' => 'nullable|integer|min:1',
            'editingStatus' => 'required|in:registered,checked_in,canceled',
        ]);

        $this->editingParticipant->update([
            'team_id' => $this->editingTeamId,
            'seed_number' => $this->editingSeedNumber,
            'status' => $this->editingStatus,
            'has_checked_in' => $this->editingStatus === 'checked_in',
            'checked_in_at' => ($this->editingStatus === 'checked_in' && !$this->editingParticipant->has_checked_in) ? now() : $this->editingParticipant->checked_at,
        ]);

        $this->closeEdit();
        $this->dispatch('toast', message: __('Registration updated successfully.'), type: 'success');
    }

    public function closeEdit()
    {
        $this->editingParticipant = null;
        $this->editingParticipantId = null;
        $this->editingTeamId = null;
        $this->editingSeedNumber = null;
        $this->editingStatus = '';
        \Flux\Flux::modal('edit-registration-modal')->close();
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
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
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
