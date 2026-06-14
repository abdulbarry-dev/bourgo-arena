<?php

namespace App\Livewire\Admin\Events;

use App\Models\Event;
use App\Models\Service;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class EventManager extends Component
{
    use WithFileUploads, WithPagination;

    public $search = '';

    public $statusFilter = '';

    public $serviceIdFilter = '';

    // Form fields
    public $isCreateModalOpen = false;

    public $editingEventId = null;

    public $name = '';

    public $description = '';

    public $sport_type = 'padel';

    public $service_id = null;

    public $format = '1v1';

    public $max_participants = 16;

    public $requires_check_in = false;

    public $status = 'draft';

    public $registration_deadline = null;

    public $start_date = null;

    public $end_date = null;

    // Media properties
    public $images = [];

    public $newImages = [];

    public $uploadQueue = [];

    public $imageToDeleteIndex = null;

    public $isNewImageDeletion = false;

    public ?Event $eventToView = null;

    public ?Event $eventToDelete = null;

    public ?Event $eventToCancel = null;

    public string $deleteConfirmName = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedServiceIdFilter()
    {
        $this->resetPage();
    }

    public function processUploadQueue()
    {
        $this->validate([
            'uploadQueue.*' => 'image|max:2048', // 2MB Max
        ]);

        foreach ($this->uploadQueue as $file) {
            if (count($this->images) + count($this->newImages) < 3) {
                $this->newImages[] = $file;
            } else {
                $this->dispatch('toast', message: __('Maximum of 3 images allowed.'), type: 'danger');
                break;
            }
        }

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
        $this->eventToView = $event->loadCount('participants');
        Flux::modal('view-event-modal')->show();
    }

    public function closeViewModal()
    {
        $this->eventToView = null;
    }

    public function openCancelModal(Event $event)
    {
        $this->eventToCancel = $event;
        Flux::modal('cancel-event-modal')->show();
    }

    public function confirmCancel()
    {
        $this->authorize('update', $this->eventToCancel);

        try {
            $this->eventToCancel->cancel();
            $this->dispatch('toast', message: __('Event canceled successfully.'), type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'danger');
        }

        Flux::modal('cancel-event-modal')->close();
        $this->eventToCancel = null;
    }

    public function openDeleteModal(Event $event)
    {
        $this->eventToDelete = $event;
        $this->deleteConfirmName = '';
        Flux::modal('delete-event-modal')->show();
    }

    public function confirmDelete()
    {
        $this->authorize('delete', $this->eventToDelete);

        if ($this->deleteConfirmName !== $this->eventToDelete->name) {
            $this->dispatch('toast', message: __('Event name does not match. Confirmation failed.'), type: 'danger');

            return;
        }

        $this->eventToDelete->delete();
        $this->dispatch('toast', message: __('Event deleted successfully.'), type: 'success');

        Flux::modal('delete-event-modal')->close();
        $this->eventToDelete = null;
    }

    public function openCreateModal()
    {
        $this->resetForm();
        Flux::modal('create-event-modal')->show();
    }

    public function closeCreateModal()
    {
        Flux::modal('create-event-modal')->close();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'service_id' => 'required|exists:services,id',
            'format' => 'required|string',
            'max_participants' => 'required|integer|min:2',
        ]);

        $imagePaths = $this->images;
        foreach ($this->newImages as $image) {
            $imagePaths[] = $image->store('events', 'public');
        }

        Event::updateOrCreate(
            ['id' => $this->editingEventId],
            [
                'service_id' => $this->service_id,
                'name' => $this->name,
                'description' => $this->description,
                'format' => $this->format,
                'max_participants' => $this->max_participants,
                'requires_check_in' => $this->requires_check_in,
                'registration_deadline' => $this->registration_deadline,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'images' => $imagePaths,
            ]
        );

        $this->closeCreateModal();
        $this->dispatch('toast', message: $this->editingEventId ? __('Event updated successfully.') : __('Event created successfully.'), type: 'success');
    }

    public function edit(Event $event)
    {
        $this->editingEventId = $event->id;
        $this->name = $event->name;
        $this->description = $event->description;
        $this->service_id = $event->service_id;
        $this->format = $event->format;
        $this->max_participants = $event->max_participants;
        $this->requires_check_in = $event->requires_check_in;
        $this->registration_deadline = $event->registration_deadline ? $event->registration_deadline->format('Y-m-d\TH:i') : null;
        $this->start_date = $event->start_date ? $event->start_date->format('Y-m-d\TH:i') : null;
        $this->end_date = $event->end_date ? $event->end_date->format('Y-m-d\TH:i') : null;
        $this->images = $event->images ?? [];
        $this->newImages = [];
        $this->uploadQueue = [];

        Flux::modal('create-event-modal')->show();
    }

    private function resetForm()
    {
        $this->reset([
            'editingEventId', 'name', 'description', 'service_id', 'format',
            'max_participants', 'requires_check_in', 'registration_deadline',
            'start_date', 'end_date', 'images', 'newImages', 'uploadQueue',
        ]);
    }

    #[Computed]
    public function availableServices()
    {
        return Service::all();
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
            ->paginate(6);

        return view('livewire.admin.events.event-manager', [
            'events' => $events,
        ]);
    }
}
