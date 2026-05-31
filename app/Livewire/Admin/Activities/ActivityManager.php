<?php

namespace App\Livewire\Admin\Activities;

use App\Models\Activity;
use App\Models\ActivitySlot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ActivityManager extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public bool $showActivityFlyout = false;

    public bool $showDetailFlyout = false;

    public ?int $activityId = null;

    public ?int $detailActivityId = null;

    public string $title = '';

    public string $category = 'padel';

    public string $basePrice = '';

    public string $currency = 'TND';

    public ?string $description = null;

    public string $featuresInput = '';

    public array $images = [];

    public bool $isActive = true;

    public string $slotDate = '';

    public string $slotStartsAt = '10:00';

    public string $slotEndsAt = '11:00';

    public int|string $slotCapacity = 10;

    public bool $slotIsAvailable = true;

    public ?int $editingSlotId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateFlyout(): void
    {
        $this->resetValidation();
        $this->resetActivityForm();
        $this->showActivityFlyout = true;
    }

    public function openEditFlyout(int $id): void
    {
        $activity = Activity::query()->findOrFail($id);

        $this->resetValidation();
        $this->activityId = $activity->id;
        $this->title = $activity->title;
        $this->category = $activity->category;
        $this->basePrice = number_format((float) $activity->base_price, 2, '.', '');
        $this->currency = $activity->currency;
        $this->description = $activity->description;
        $this->featuresInput = implode(', ', $activity->features ?? []);
        $this->isActive = $activity->is_active;
        $this->showActivityFlyout = true;
    }

    public function openDetailFlyout(int $id): void
    {
        $this->detailActivityId = $id;
        $this->resetSlotForm();
        $this->showDetailFlyout = true;
    }

    public function closeActivityFlyout(): void
    {
        $this->showActivityFlyout = false;
    }

    public function closeDetailFlyout(): void
    {
        $this->showDetailFlyout = false;
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules());

        $payload = [
            'title' => $validated['title'],
            'category' => $validated['category'],
            'base_price' => $validated['basePrice'],
            'currency' => $validated['currency'],
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

            $this->showActivityFlyout = false;
            $this->dispatch('toast', message: __('Activity created successfully.'), type: 'success');

            return;
        }

        $activity = Activity::query()->findOrFail($this->activityId);
        $activity->update($payload);

        $this->showActivityFlyout = false;
        $this->dispatch('toast', message: __('Activity updated successfully.'), type: 'success');
    }

    public function saveSlot(): void
    {
        if ($this->detailActivityId === null) {
            throw ValidationException::withMessages([
                'slotDate' => [__('Select an activity before adding a slot.')],
            ]);
        }

        $validated = $this->validate($this->slotRules());
        $activity = Activity::query()->findOrFail($this->detailActivityId);
        if ($this->editingSlotId === null) {
            $activity->slots()->create([
                'date' => $validated['slotDate'],
                'starts_at' => $validated['slotStartsAt'].':00',
                'ends_at' => $validated['slotEndsAt'].':00',
                'capacity' => $validated['slotCapacity'],
                'booked_count' => 0,
                'is_available' => $validated['slotIsAvailable'],
            ]);

            $this->resetSlotForm();
            $this->dispatch('toast', message: __('Slot created successfully.'), type: 'success');

            return;
        }

        $slot = ActivitySlot::query()->findOrFail($this->editingSlotId);
        $slot->update([
            'date' => $validated['slotDate'],
            'starts_at' => $validated['slotStartsAt'].':00',
            'ends_at' => $validated['slotEndsAt'].':00',
            'capacity' => $validated['slotCapacity'],
            'is_available' => $validated['slotIsAvailable'],
        ]);

        $this->editingSlotId = null;
        $this->resetSlotForm();
        $this->dispatch('toast', message: __('Slot updated successfully.'), type: 'success');
    }

    public function openSlotEdit(int $slotId): void
    {
        $slot = ActivitySlot::query()->findOrFail($slotId);

        $this->editingSlotId = $slot->id;
        $this->slotDate = $slot->date->toDateString();
        $this->slotStartsAt = substr($slot->starts_at, 0, 5);
        $this->slotEndsAt = substr($slot->ends_at, 0, 5);
        $this->slotCapacity = $slot->capacity;
        $this->slotIsAvailable = $slot->is_available;
    }

    public function cancelSlotEdit(): void
    {
        $this->editingSlotId = null;
        $this->resetSlotForm();
    }

    public function toggleSlotAvailability(int $slotId): void
    {
        $slot = ActivitySlot::query()->findOrFail($slotId);
        $slot->is_available = ! $slot->is_available;
        $slot->save();

        $this->dispatch('toast', message: __('Slot availability updated.'), type: 'success');
    }

    public function deleteSlot(int $slotId): void
    {
        $slot = ActivitySlot::query()->findOrFail($slotId);
        $slot->delete();

        $this->dispatch('toast', message: __('Slot deleted.'), type: 'success');
    }

    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        return Activity::query()
            ->withCount('slots')
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';

                $query->where(function (Builder $builder) use ($term): void {
                    $builder
                        ->where('title', 'like', $term)
                        ->orWhere('category', 'like', $term);
                });
            })
            ->when($this->categoryFilter !== '', fn (Builder $query) => $query->where('category', $this->categoryFilter))
            ->orderBy('title')
            ->paginate(10);
    }

    #[Computed]
    public function selectedActivity(): ?Activity
    {
        if ($this->detailActivityId === null) {
            return null;
        }

        return Activity::query()
            ->with([
                'slots' => function ($query): void {
                    $query->withCount('reservations')->orderBy('date')->orderBy('starts_at');
                },
            ])
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
            'title',
            'category',
            'basePrice',
            'currency',
            'description',
            'featuresInput',
            'images',
            'isActive',
        ]);

        $this->category = 'padel';
        $this->currency = 'TND';
        $this->isActive = true;
    }

    private function resetSlotForm(): void
    {
        $this->reset(['slotDate', 'slotStartsAt', 'slotEndsAt', 'slotCapacity', 'slotIsAvailable']);
        $this->slotStartsAt = '10:00';
        $this->slotEndsAt = '11:00';
        $this->slotCapacity = 10;
        $this->slotIsAvailable = true;
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'basePrice' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string'],
            'featuresInput' => ['nullable', 'string'],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['image', 'max:2048'],
            'isActive' => ['boolean'],
        ];
    }

    private function slotRules(): array
    {
        return [
            'slotDate' => ['required', 'date'],
            'slotStartsAt' => ['required', 'date_format:H:i'],
            'slotEndsAt' => ['required', 'date_format:H:i', 'after:slotStartsAt'],
            'slotCapacity' => ['required', 'integer', 'min:1'],
            'slotIsAvailable' => ['boolean'],
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
