<?php

namespace App\Livewire\Admin\Events;

use App\Events\EventDeleted;
use App\Models\Event;
use App\Models\Service;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class EventManager extends Component
{
    use WithFileUploads, WithPagination;

    public $search = '';

    public $serviceIdFilter = '';

    // Form fields
    public $isCreateModalOpen = false;

    public $editingEventId = null;

    public ?Event $eventToCancel = null;

    public ?Event $eventToDelete = null;

    public ?Event $eventToView = null;

    public $imageToDeleteIndex = null;

    public $isNewImageDeletion = false;

    public $deleteConfirmName = '';

    public ?int $service_id = null;

    public $name = '';

    public $description = '';

    public $format = '1v1';

    public $max_participants = 2;

    public $registration_deadline = null;

    public $start_date = null;

    public $end_date = null;

    public $requires_check_in = false;

    public $images = []; // Existing image paths

    public $newImages = []; // Persistent collection of new uploads (temporary files)

    public $uploadQueue = []; // Temporary target for the latest file selection

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedServiceIdFilter()
    {
        $this->resetPage();
    }

    public function updatedUploadQueue()
    {
        $this->validate([
            'uploadQueue.*' => 'image|max:2048', // 2MB Max
        ]);

        foreach ($this->uploadQueue as $file) {
            if (count($this->images) + count($this->newImages) < 3) {
                $this->newImages[] = $file;
            } else {
                Flux::toast('Maximum of 3 images allowed.', variant: 'danger');
                break;
            }
        }

        $this->dispatch('clear-upload-queue');
    }

    #[On('clear-upload-queue')]
    public function clearUploadQueue()
    {
        $this->uploadQueue = [];
    }

    public function confirmImageDeletion($index, $isNew = false)
    {
        $this->imageToDeleteIndex = $index;
        $this->isNewImageDeletion = $isNew;
        Flux::modal('confirm-image-delete')->show();
    }

    public function executeImageDeletion()
    {
        if ($this->isNewImageDeletion) {
            array_splice($this->newImages, $this->imageToDeleteIndex, 1);
        } else {
            array_splice($this->images, $this->imageToDeleteIndex, 1);
        }

        $this->closeImageDeleteModal();
    }

    public function closeImageDeleteModal()
    {
        Flux::modal('confirm-image-delete')->close();
        $this->imageToDeleteIndex = null;
        $this->isNewImageDeletion = false;
    }

    public function clearNewImages()
    {
        $this->newImages = [];
        $this->uploadQueue = [];
    }

    public function openViewModal(Event $event)
    {
        $this->eventToView = $event;
        Flux::modal('view-event-modal')->show();
    }

    public function closeViewModal()
    {
        Flux::modal('view-event-modal')->close();
        $this->eventToView = null;
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
            'newImages.*' => 'image|max:2048',
        ]);

        if (! Service::where('id', $this->service_id)->exists()) {
            throw ValidationException::withMessages(['service_id' => 'The selected service is invalid.']);
        }

        DB::transaction(function (): void {
            $imagePaths = $this->images;

            foreach ($this->newImages as $image) {
                $imagePaths[] = $image->store('events', 'public');
            }

            $eventData = [
                'service_id' => $this->service_id,
                'name' => $this->name,
                'description' => $this->description,
                'images' => $imagePaths,
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
        $this->images = $event->images ?? [];
        $this->newImages = []; // Clear any temporary uploads
        $this->uploadQueue = [];
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
            'editingEventId', 'service_id', 'name', 'description', 'images', 'newImages', 'uploadQueue', 'format',
            'max_participants', 'requires_check_in', 'registration_deadline',
            'start_date', 'end_date', 'eventToCancel', 'eventToDelete', 'deleteConfirmName',
        ]);
    }

    public function openCancelModal(Event $event)
    {
        if (! in_array($event->status, ['draft', 'open'])) {
            Flux::toast('Only draft or open events can be canceled.', variant: 'danger');

            return;
        }

        $this->eventToCancel = $event;
        Flux::modal('cancel-event-modal')->show();
    }

    public function confirmCancel()
    {
        if ($this->eventToCancel) {
            $this->eventToCancel->cancel();
            Flux::toast('Event canceled successfully.', variant: 'success');
        }

        Flux::modal('cancel-event-modal')->close();
        $this->resetForm();
    }

    public function openDeleteModal(Event $event)
    {
        $this->eventToDelete = $event;
        $this->deleteConfirmName = '';
        Flux::modal('delete-event-modal')->show();
    }

    public function confirmDelete()
    {
        if ($this->eventToDelete && $this->deleteConfirmName === $this->eventToDelete->name) {
            $this->eventToDelete->delete();
            event(new EventDeleted($this->eventToDelete));
            Flux::toast('Event deleted successfully.', variant: 'success');
        } else {
            Flux::toast('Event name does not match.', variant: 'danger');

            return;
        }

        Flux::modal('delete-event-modal')->close();
        $this->resetForm();
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
