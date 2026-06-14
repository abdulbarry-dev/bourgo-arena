<?php

namespace App\Livewire\Admin\Activities;

use App\Models\Activity;
use App\Models\ActivitySlot;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ActivitySlotsManager extends Component
{
    use WithPagination;

    public Activity $activity;

    public bool $showActivityModal = false;

    public ?int $editingActivityId = null;

    public string $activityTitle = '';

    public string $activityBasePrice = '';

    public ?int $activityCapacity = null;

    public ?string $activityDescription = null;

    public string $activityFeaturesInput = '';

    public bool $activityIsActive = true;

    public string $slotStartsAt = '10:00';

    public string $slotEndsAt = '11:00';

    public int|string $slotCapacity = 10;

    public bool $slotIsAvailable = true;

    public bool $showSlotModal = false;

    public ?int $editingSlotId = null;

    public function mount(Activity $activity): void
    {
        $this->activity = $activity;
        $this->resetSlotForm();
    }

    public function openEditActivityModal(): void
    {
        $this->resetValidation();
        $this->activity->refresh();

        $this->editingActivityId = $this->activity->id;
        $this->activityTitle = $this->activity->title;
        $this->activityBasePrice = number_format((float) $this->activity->base_price, 2, '.', '');
        $this->activityCapacity = $this->activity->capacity;
        $this->activityDescription = $this->activity->description;
        $this->activityFeaturesInput = implode(', ', $this->activity->features ?? []);
        $this->activityIsActive = $this->activity->is_active;
        $this->showActivityModal = true;
    }

    public function closeActivityModal(): void
    {
        $this->showActivityModal = false;
        $this->editingActivityId = null;
        $this->resetActivityForm();
    }

    public function openCreateSlotModal(): void
    {
        $this->editingSlotId = null;
        $this->resetSlotForm();
        $this->showSlotModal = true;
    }

    public function openEditSlotModal(int $slotId): void
    {
        $slot = ActivitySlot::query()
            ->where('activity_id', $this->activity->id)
            ->findOrFail($slotId);

        $this->editingSlotId = $slot->id;
        $this->slotStartsAt = substr($slot->starts_at, 0, 5);
        $this->slotEndsAt = substr($slot->ends_at, 0, 5);
        $this->slotCapacity = $slot->capacity;
        $this->slotIsAvailable = $slot->is_available;
        $this->showSlotModal = true;
    }

    public function closeSlotModal(): void
    {
        $this->showSlotModal = false;
        $this->editingSlotId = null;
        $this->resetSlotForm();
    }

    public function saveSlot(): void
    {
        $validated = $this->validate($this->slotRules());

        if ($this->editingSlotId === null) {
            // prevent overlapping time ranges for same activity
            $starts = $validated['slotStartsAt'].':00';
            $ends = $validated['slotEndsAt'].':00';
            if (ActivitySlot::overlaps($this->activity->id, $starts, $ends)) {
                $this->addError('slotStartsAt', __('Slot time overlaps an existing slot for this activity.'));
                $this->addError('slotEndsAt', __('Slot time overlaps an existing slot for this activity.'));

                return;
            }

            $this->activity->slots()->create([
                'starts_at' => $starts,
                'ends_at' => $ends,
                'capacity' => $validated['slotCapacity'],
                'is_available' => $validated['slotIsAvailable'],
            ]);

            $this->closeSlotModal();
            $this->dispatch('toast', message: __('Slot created successfully.'), type: 'success');

            return;
        }

        $slot = ActivitySlot::query()
            ->where('activity_id', $this->activity->id)
            ->findOrFail($this->editingSlotId);

        $starts = $validated['slotStartsAt'].':00';
        $ends = $validated['slotEndsAt'].':00';

        if (ActivitySlot::overlaps($this->activity->id, $starts, $ends, $this->editingSlotId)) {
            $this->addError('slotStartsAt', __('Slot time overlaps an existing slot for this activity.'));
            $this->addError('slotEndsAt', __('Slot time overlaps an existing slot for this activity.'));

            return;
        }

        $slot->update([
            'starts_at' => $starts,
            'ends_at' => $ends,
            'capacity' => $validated['slotCapacity'],
            'is_available' => $validated['slotIsAvailable'],
        ]);

        $this->closeSlotModal();
        $this->dispatch('toast', message: __('Slot updated successfully.'), type: 'success');
    }

    public function toggleSlotAvailability(int $slotId): void
    {
        $slot = ActivitySlot::query()
            ->where('activity_id', $this->activity->id)
            ->findOrFail($slotId);

        $slot->is_available = ! $slot->is_available;
        $slot->save();

        $this->dispatch('toast', message: __('Slot availability updated.'), type: 'success');
    }

    public function deleteSlot(int $slotId): void
    {
        ActivitySlot::query()
            ->where('activity_id', $this->activity->id)
            ->findOrFail($slotId)
            ->delete();

        $this->dispatch('toast', message: __('Slot deleted.'), type: 'success');
    }

    public function saveActivity(): void
    {
        $validated = $this->validate($this->activityRules());

        $this->activity->update([
            'title' => $validated['activityTitle'],
            'base_price' => $validated['activityBasePrice'],
            'capacity' => $validated['activityCapacity'],
            'description' => $validated['activityDescription'] ?: null,
            'features' => $this->normalizeFeatures($validated['activityFeaturesInput']),
            'is_active' => $validated['activityIsActive'],
        ]);

        $this->activity->refresh();
        $this->closeActivityModal();
        $this->dispatch('toast', message: __('Activity updated successfully.'), type: 'success');
    }

    #[Computed]
    public function paginatedSlots(): LengthAwarePaginator
    {
        return $this->activity->slots()
            ->orderBy('starts_at')
            ->paginate(10);
    }

    public function render(): View
    {
        $this->activity->loadCount('slots');

        return view('livewire.admin.activities.activity-slots-manager');
    }

    private function resetSlotForm(): void
    {
        $this->reset(['slotStartsAt', 'slotEndsAt', 'slotCapacity', 'slotIsAvailable']);
        $this->slotStartsAt = '10:00';
        $this->slotEndsAt = '11:00';
        $this->slotCapacity = 10;
        $this->slotIsAvailable = true;
    }

    private function resetActivityForm(): void
    {
        $this->reset([
            'editingActivityId',
            'activityTitle',
            'activityBasePrice',
            'activityCapacity',
            'activityDescription',
            'activityFeaturesInput',
            'activityIsActive',
        ]);

        $this->activityIsActive = true;
    }

    /**
     * @return array<string, list<string>>
     */
    private function slotRules(): array
    {
        return [
            'slotStartsAt' => ['required', 'date_format:H:i'],
            'slotEndsAt' => ['required', 'date_format:H:i', 'after:slotStartsAt'],
            'slotCapacity' => ['required', 'integer', 'min:1'],
            'slotIsAvailable' => ['boolean'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function activityRules(): array
    {
        return [
            'activityTitle' => ['required', 'string', 'max:255'],
            'activityBasePrice' => ['required', 'numeric', 'min:0'],
            'activityCapacity' => ['required', 'integer', 'min:1'],
            'activityDescription' => ['required', 'string'],
            'activityFeaturesInput' => ['required', 'string'],
            'activityIsActive' => ['boolean'],
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
