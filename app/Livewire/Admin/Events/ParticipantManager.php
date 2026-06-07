<?php

namespace App\Livewire\Admin\Events;

use App\Models\Event;
use App\Models\EventParticipant;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

class ParticipantManager extends Component
{
    use WithPagination;

    public Event $event;

    public $search = '';

    public $statusFilter = '';

    // Manage status modal
    public $isStatusModalOpen = false;

    public $managingParticipantId = null;

    public $newStatus = '';

    public $newSeedNumber = null;

    public function mount(Event $event)
    {
        $this->event = $event;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function manage(EventParticipant $participant)
    {
        $this->managingParticipantId = $participant->id;
        $this->newStatus = $participant->status;
        $this->newSeedNumber = $participant->seed_number;
        Flux::modal('manage-status-modal')->show();
    }

    public function closeStatusModal()
    {
        $this->managingParticipantId = null;
        Flux::modal('manage-status-modal')->close();
    }

    public function saveStatus()
    {
        $this->validate([
            'newStatus' => 'required|string',
            'newSeedNumber' => 'nullable|integer|min:1',
        ]);

        $participant = EventParticipant::find($this->managingParticipantId);

        if ($participant) {
            $participant->update([
                'status' => $this->newStatus,
                'seed_number' => $this->newSeedNumber,
            ]);

            if ($this->newStatus === 'withdrawn' && ! $participant->withdrawn_at) {
                $participant->update(['withdrawn_at' => now()]);
            } elseif ($this->newStatus !== 'withdrawn') {
                $participant->update(['withdrawn_at' => null]);
            }
        }

        $this->closeStatusModal();
    }

    public function render()
    {
        $participants = $this->event->participants()
            ->with('user')
            ->when($this->search, function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('seed_number', 'asc') // sort seeded players first
            ->latest()
            ->paginate(15);

        return view('livewire.admin.events.participant-manager', [
            'participants' => $participants,
        ]);
    }
}
