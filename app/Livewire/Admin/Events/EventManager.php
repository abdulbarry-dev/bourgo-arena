<?php

namespace App\Livewire\Admin\Events;

use App\Models\Event;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class EventManager extends Component
{
    use WithPagination;

    public $search = '';

    public $statusFilter = '';

    // Form fields
    public $isCreateModalOpen = false;

    public $editingEventId = null;

    public $name = '';

    public $description = '';

    public $sport_type = 'padel';

    public $format = '1v1';

    public $max_participants = 16;

    public $requires_check_in = false;

    public $status = 'draft';

    public $registration_deadline = null;

    public $start_date = null;

    public $end_date = null;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetForm();
        Flux::modal('create-event-modal')->show();
    }

    public function closeCreateModal()
    {
        Flux::modal('create-event-modal')->close();
        $this->resetForm();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'sport_type' => 'required|string',
            'format' => 'required|string',
            'max_participants' => 'required|integer|min:2',
            'status' => 'required|string',
        ]);

        DB::transaction(function (): void {
            $eventData = [
                'name' => $this->name,
                'description' => $this->description,
                'sport_type' => $this->sport_type,
                'format' => $this->format,
                'max_participants' => $this->max_participants,
                'requires_check_in' => $this->requires_check_in,
                'status' => $this->status,
                'registration_deadline' => $this->registration_deadline,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
            ];

            if ($this->editingEventId) {
                $event = Event::findOrFail($this->editingEventId);
                $event->matches()->delete();
                $event->update($eventData);
            } else {
                Event::create($eventData);
            }
        });

        $this->closeCreateModal();
    }

    public function edit(Event $event)
    {
        $this->editingEventId = $event->id;
        $this->name = $event->name;
        $this->description = $event->description;
        $this->sport_type = $event->sport_type;
        $this->format = $event->format;
        $this->max_participants = $event->max_participants;
        $this->requires_check_in = $event->requires_check_in;
        $this->status = $event->status;
        $this->registration_deadline = $event->registration_deadline ? $event->registration_deadline->format('Y-m-d\TH:i') : null;
        $this->start_date = $event->start_date ? $event->start_date->format('Y-m-d\TH:i') : null;
        $this->end_date = $event->end_date ? $event->end_date->format('Y-m-d\TH:i') : null;

        Flux::modal('create-event-modal')->show();
    }

    private function resetForm()
    {
        $this->reset([
            'editingEventId', 'name', 'description', 'sport_type', 'format',
            'max_participants', 'requires_check_in', 'status', 'registration_deadline',
            'start_date', 'end_date',
        ]);
    }

    public function render()
    {
        $events = Event::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->withCount('participants')
            ->latest()
            ->paginate(10);

        return view('livewire.admin.events.event-manager', [
            'events' => $events,
        ]);
    }
}
