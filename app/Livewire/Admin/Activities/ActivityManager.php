<?php

namespace App\Livewire\Admin\Activities;

use App\Models\Activity;
use App\Models\Service;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ActivityManager extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public ?int $serviceFilter = null;

    public string $statusFilter = '';

    public bool $showActivityFlyout = false;

    public bool $showDetailFlyout = false;

    public ?int $activityId = null;

    public ?int $serviceId = null;

    public ?int $detailActivityId = null;

    public $imageToDeleteIndex = null;

    public $isNewImageDeletion = false;

    public string $title = '';

    public string $basePrice = '';

    public ?int $capacity = null;

    public ?string $description = null;

    public string $featuresInput = '';

    public array $images = []; // Existing image paths

    public $newImages = []; // Persistent collection of new uploads (temporary files)

    public $uploadQueue = []; // Temporary target for the latest file selection

    public bool $isActive = true;

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

    public function removeImage($index)
    {
        array_splice($this->images, $index, 1);
    }

    public function removeNewImage($index)
    {
        array_splice($this->newImages, $index, 1);
    }

    public function clearNewImages()
    {
        $this->newImages = [];
        $this->uploadQueue = [];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedServiceFilter(): void // New method for service filter update
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedServiceId($value)
    {
        $this->serviceId = (int) $value;
    }

    public function openCreateFlyout(): void
    {
        $this->resetValidation();
        $this->resetActivityForm();

        // Pre-select the first available active service if any exist
        $firstAvailableService = $this->availableServices->first();
        if ($firstAvailableService) {
            $this->serviceId = $firstAvailableService->id;
        }

        $this->showActivityFlyout = true;
    }

    public function openEditFlyout(int $id): void
    {
        $activity = Activity::query()->findOrFail($id);

        $this->resetValidation();
        $this->activityId = $activity->id;
        $this->serviceId = $activity->service_id;
        $this->title = $activity->title;
        $this->basePrice = number_format((float) $activity->base_price, 2, '.', '');
        $this->capacity = $activity->capacity;
        $this->description = $activity->description;
        $this->featuresInput = implode(', ', $activity->features ?? []);
        $this->images = $activity->images ?? [];
        $this->newImages = [];
        $this->uploadQueue = [];
        $this->isActive = $activity->is_active;
        $this->showActivityFlyout = true;
    }

    #[On('open-activity-detail')]
    public function openDetailFlyout(int $id): void
    {
        $this->detailActivityId = $id;
        $this->showDetailFlyout = true;
    }

    public function closeActivityFlyout(): void
    {
        $this->showActivityFlyout = false;
        $this->resetActivityForm();
    }

    public function closeDetailFlyout(): void
    {
        $this->showDetailFlyout = false;
        $this->detailActivityId = null;
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules());

        $imagePaths = $this->images;
        foreach ($this->newImages as $image) {
            $imagePaths[] = $image->store('activities', 'public');
        }

        $payload = [
            'service_id' => $validated['serviceId'],
            'title' => $validated['title'],
            'base_price' => $validated['basePrice'],
            'capacity' => $validated['capacity'],
            'description' => $validated['description'] ?: null,
            'features' => $this->normalizeFeatures($validated['featuresInput']),
            'is_active' => $validated['isActive'],
            'images' => $imagePaths,
        ];

        if ($this->activityId === null) {
            Activity::query()->create($payload);

            $this->closeActivityFlyout();
            $this->dispatch('toast', message: __('Activity created successfully.'), type: 'success');

            return;
        }

        $activity = Activity::query()->findOrFail($this->activityId);
        $activity->update($payload);

        $this->closeActivityFlyout();
        $this->dispatch('toast', message: __('Activity updated successfully.'), type: 'success');
    }

    #[Computed]
    public function availableServices()
    {
        return Service::query()->active()->orderBy('name')->get();
    }

    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        $activities = Activity::query()
            ->withCount('sessions')
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';

                $query->where(function (Builder $builder) use ($term): void {
                    $builder
                        ->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhereJsonContains('features', $term);
                });
            })
            ->when($this->serviceFilter, function (Builder $query) {
                $query->where('service_id', $this->serviceFilter);
            })
            ->when($this->statusFilter !== '', function (Builder $query) {
                $query->where('is_active', $this->statusFilter === 'active');
            })
            ->orderBy('title')
            ->paginate(10);

        return $activities;
    }

    #[Computed]
    public function selectedActivity(): ?Activity
    {
        if ($this->detailActivityId === null) {
            return null;
        }

        return Activity::query()
            ->withCount('sessions')
            ->find($this->detailActivityId);
    }

    public function render(): View
    {
        return view('livewire.admin.activities.activity-manager');
    }

    private function resetActivityForm(): void
    {
        $this->reset([
            'activityId',
            'serviceId',
            'title',
            'basePrice',
            'capacity',
            'description',
            'featuresInput',
            'images',
            'newImages',
            'uploadQueue',
            'isActive',
        ]);

        $this->isActive = true;
    }

    private function rules(): array
    {
        return [
            'serviceId' => ['required', 'integer', 'exists:services,id'],
            'title' => ['required', 'string', 'max:255'],
            'basePrice' => ['required', 'numeric', 'min:0'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'featuresInput' => ['nullable', 'string'],
            'isActive' => ['boolean'],
        ];
    }

    private function normalizeFeatures(?string $featuresInput): array
    {
        return collect(explode(',', (string) $featuresInput))
            ->map(fn (string $feature): string => trim($feature))
            ->filter()
            ->values()
            ->all();
    }
}
