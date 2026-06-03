<?php

namespace App\Livewire\Admin\Activities;

use App\Models\Activity;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ActivityManager extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public ?int $serviceFilter = null; // New property for service filter

    public string $statusFilter = ''; // New property for status filter

    public string $categoryFilter = ''; // New property for category filter

    public bool $showActivityFlyout = false;

    public bool $showDetailFlyout = false;

    public ?int $activityId = null;

    public ?int $serviceId = null;

    public ?int $detailActivityId = null;

    public string $title = '';

    public string $category = '';

    public string $basePrice = '';

    public ?string $description = null;

    public string $featuresInput = '';

    public array $images = [];

    public bool $isActive = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedServiceFilter(): void // New method for service filter update
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void // New method for status filter update
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void // New method for category filter update
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
        $this->category = $activity->category;
        $this->basePrice = number_format((float) $activity->base_price, 2, '.', '');
        $this->description = $activity->description;
        $this->featuresInput = implode(', ', $activity->features ?? []);
        $this->isActive = $activity->is_active;
        $this->showActivityFlyout = true;
    }

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

        $payload = [
            'service_id' => $validated['serviceId'],
            'title' => $validated['title'],
            'category' => $validated['category'],
            'base_price' => $validated['basePrice'],
            'currency' => 'TND',
            'description' => $validated['description'] ?: null,
            'features' => $this->normalizeFeatures($validated['featuresInput']),
            'is_active' => $validated['isActive'],
        ];

        if (! empty($this->images)) {
            $uploadedImages = [];
            foreach ($this->images as $image) {
                $uploadedImages[] = $image->store('activities', 'public');
            }
            $payload['images'] = $uploadedImages;
        }

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
    public function categories()
    {
        return Activity::query()->select('category')->distinct()->orderBy('category')->pluck('category')->filter()->values();
    }

    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        $activities = Activity::query()
            ->withCount('slots')
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';

                $query->where(function (Builder $builder) use ($term): void {
                    $builder
                        ->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term) // Search description
                        ->orWhereJsonContains('features', $term); // Search features (assuming features is JSON)
                });
            })
            ->when($this->serviceFilter, function (Builder $query) { // Apply service filter
                $query->where('service_id', $this->serviceFilter);
            })
            ->when($this->categoryFilter !== '', function (Builder $query) { // Apply category filter
                $query->where('category', $this->categoryFilter);
            })
            ->when($this->statusFilter !== '', function (Builder $query) { // Apply status filter
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
            ->withCount('slots')
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
            'category',
            'basePrice',
            'description',
            'featuresInput',
            'images',
            'isActive',
        ]);

        $this->isActive = true;
    }

    private function rules(): array
    {
        return [
            'serviceId' => ['required', 'integer', 'exists:services,id'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'basePrice' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'featuresInput' => ['nullable', 'string'],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['image', 'max:2048'],
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
