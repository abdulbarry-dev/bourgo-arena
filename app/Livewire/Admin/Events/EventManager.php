<?php

namespace App\Livewire\Admin\Events;

use App\Models\Event;
use App\Models\Service;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class EventManager extends Component
{
    use WithPagination;

    public $search = '';

    public $serviceIdFilter = '';

    // Form fields
    public $isCreateModalOpen = false;

    public $editingEventId = null;

    public ?int $service_id = null;

    public $name = '';

    public $description = '';

    public $format = '1v1';

    public $max_participants = 16;

    public $requires_check_in = false;

    public $registration_deadline = null;

    public $start_date = null;

    public $end_date = null;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedServiceIdFilter()
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
            'service_id' => 'required',
            'name' => 'required|string|max:255',
            'format' => 'required|string',
            'max_participants' => 'required|integer|min:2',
        ]);

        if (! Service::where('id', $this->service_id)->exists()) {
            throw ValidationException::withMessages(['service_id' => 'The selected service is invalid.']);
        }

        DB::transaction(function (): void {
            $eventData = [
                'service_id' => $this->service_id,
                'name' => $this->name,
                'description' => $this->description,
                'format' => $this->format,
                'max_participants' => $this->max_participants,
                'requires_check_in' => $this->requires_check_in,
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
        $this->service_id = $event->service_id;
        $this->name = $event->name;
        $this->description = $event->description;
        $this->format = $event->format;
        $this->max_participants = $event->max_participants;
        $this->requires_check_in = $event->requires_check_in;
        $this->registration_deadline = $event->registration_deadline ? $event->registration_deadline->format('Y-m-d\TH:i') : null;
        $this->start_date = $event->start_date ? $event->start_date->format('Y-m-d\TH:i') : null;
        $this->end_date = $event->end_date ? $event->end_date->format('Y-m-d\TH:i') : null;

        Flux::modal('create-event-modal')->show();
    }

    private function resetForm()
    {
        $this->reset([
            'editingEventId', 'service_id', 'name', 'description', 'format',
            'max_participants', 'requires_check_in', 'registration_deadline',
            'start_date', 'end_date',
        ]);
    }

    #[Computed]
    public function availableServices()
    {
        return Service::orderBy('name')->get();
    }

    public function render()
    {
        $events = Event::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->when($this->serviceIdFilter, function ($query) {
                $query->where('service_id', $this->serviceIdFilter);
            })
            ->withCount('participants')
            ->latest()
            ->paginate(10);

        return view('livewire.admin.events.event-manager', [
            'events' => $events,
        ]);
    }
}
