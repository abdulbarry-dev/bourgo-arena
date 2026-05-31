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

    public string $slotDate = '';

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
        $this->slotDate = $slot->date->toDateString();
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
            $this->activity->slots()->create([
                'date' => $validated['slotDate'],
                'starts_at' => $validated['slotStartsAt'].':00',
                'ends_at' => $validated['slotEndsAt'].':00',
                'capacity' => $validated['slotCapacity'],
                'booked_count' => 0,
                'is_available' => $validated['slotIsAvailable'],
            ]);

            $this->closeSlotModal();
            $this->dispatch('toast', message: __('Slot created successfully.'), type: 'success');

            return;
        }

        $slot = ActivitySlot::query()
            ->where('activity_id', $this->activity->id)
            ->findOrFail($this->editingSlotId);

        $slot->update([
            'date' => $validated['slotDate'],
            'starts_at' => $validated['slotStartsAt'].':00',
            'ends_at' => $validated['slotEndsAt'].':00',
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

    #[Computed]
    public function paginatedSlots(): LengthAwarePaginator
    {
        return $this->activity->slots()
            ->withCount('reservations')
            ->orderBy('date')
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
        $this->reset(['slotDate', 'slotStartsAt', 'slotEndsAt', 'slotCapacity', 'slotIsAvailable']);
        $this->slotStartsAt = '10:00';
        $this->slotEndsAt = '11:00';
        $this->slotCapacity = 10;
        $this->slotIsAvailable = true;
    }

    /**
     * @return array<string, list<string>>
     */
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
}
