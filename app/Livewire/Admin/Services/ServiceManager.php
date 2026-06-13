<?php

namespace App\Livewire\Admin\Services;

use App\Models\Service;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ServiceManager extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public bool $showServiceFlyout = false;

    public bool $showViewFlyout = false;

    public ?int $serviceId = null;

    public ?Service $viewingService = null;

    public string $name = '';

    public ?string $description = null;

    public $image;

    public ?string $existingImageUrl = null;

    public string $status = 'active';

    public $images = []; // Existing image paths

    public $newImages = []; // Persistent collection of new uploads (temporary files)

    public $uploadQueue = []; // Temporary target for the latest file selection

    public $imageToDeleteIndex = null;

    public $isNewImageDeletion = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
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
                $this->dispatch('toast', message: __('Maximum of 3 images allowed.'), type: 'danger');
                break;
            }
        }

        // Use a slight delay or dispatch to clear the queue to avoid state conflicts
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

    public function openCreateFlyout(): void
    {
        $this->resetValidation();
        $this->resetForm();
        $this->showServiceFlyout = true;
    }

    public function openEditFlyout(int $id): void
    {
        $service = Service::query()->findOrFail($id);

        $this->resetValidation();
        $this->serviceId = $service->id;
        $this->name = $service->name;
        $this->description = $service->description;
        $this->images = is_array($service->images) ? $service->images : ($service->image_url ? [$service->image_url] : []);
        $this->newImages = [];
        $this->uploadQueue = [];
        $this->status = $service->status;

        $this->showServiceFlyout = true;
    }

    public function openViewFlyout(int $id): void
    {
        $this->viewingService = Service::query()
            ->withCount(['plans', 'courses', 'events', 'activities'])
            ->findOrFail($id);

        $this->showViewFlyout = true;
    }

    public function archive(int $id): void
    {
        $service = Service::query()->findOrFail($id);

        $service->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);

        $service->plans()->update(['is_archived' => true]);
        $service->courses()->update(['status' => 'archived', 'archived_at' => now()]);

        $this->dispatch('toast', message: __('Service and associated content archived successfully.'), type: 'success');
    }

    public function delete(int $id): void
    {
        $service = Service::query()->findOrFail($id);

        if ($service->hasOfferings()) {
            $this->dispatch('toast', message: __('Cannot delete service with attached offerings. Please archive it instead.'), type: 'error');

            return;
        }

        $service->delete();
        $this->dispatch('toast', message: __('Service deleted successfully.'), type: 'success');
    }

    public function restore(int $id): void
    {
        $service = Service::query()->findOrFail($id);

        $service->update([
            'status' => 'active',
            'archived_at' => null,
        ]);

        $service->plans()->update(['is_archived' => false]);
        $service->courses()->update(['status' => 'active', 'archived_at' => null]);

        $this->dispatch('toast', message: __('Service restored to active status, associated content updated.'), type: 'success');
    }

    public function closeServiceFlyout(): void
    {
        $this->showServiceFlyout = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $imagePaths = $this->images;
        foreach ($this->newImages as $image) {
            $imagePaths[] = $image->store('services', 'public');
        }

        $payload = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'status' => $this->status,
            'archived_at' => $this->status === 'archived' ? now() : null,
            'images' => $imagePaths,
            'image_url' => count($imagePaths) > 0 ? $imagePaths[0] : null,
        ];

        if ($this->serviceId) {
            $service = Service::query()->findOrFail($this->serviceId);
            $service->update($payload);
        } else {
            Service::query()->create($payload);
        }

        $this->closeServiceFlyout();
        $this->dispatch('toast', message: __('Service saved successfully.'), type: 'success');
    }

    private function resetForm(): void
    {
        $this->reset([
            'serviceId',
            'name',
            'description',
            'images',
            'newImages',
            'uploadQueue',
            'imageToDeleteIndex',
            'isNewImageDeletion',
        ]);
        $this->status = 'active';
    }

    #[Computed]
    public function services(): LengthAwarePaginator
    {
        return Service::query()
            ->when($this->search !== '', function (Builder $query) {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->when($this->statusFilter !== '', function (Builder $query) {
                $query->where('status', $this->statusFilter);
            })
            ->withCount(['plans', 'courses', 'events', 'activities'])
            ->orderBy('name')
            ->paginate(6);
    }

    public function render(): View
    {
        return view('livewire.admin.services.service-manager');
    }
}
