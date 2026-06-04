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

    public function mount(Event $event)
    {
        $this->event = $event;
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
