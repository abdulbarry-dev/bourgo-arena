<?php

namespace App\Livewire\Admin\Activities;

use App\Models\ActivitySession;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CreateActivitySessionForm extends Component
{
    #[Validate('required|integer|exists:activities,id')]
    public $activity_id = '';

    #[Validate('required|integer|min:0|max:6')]
    public $day_of_week = 0;

    #[Validate('required|string')]
    public $starts_at = '12:00';

    #[Validate('required|integer|min:15')]
    public $duration_minutes = 60;

    #[Validate('required|date')]
    public $starts_at_date;

    #[Validate('nullable|date|after_or_equal:starts_at_date')]
    public $ends_at_date;

    public function mount(): void
    {
        $this->starts_at_date = now()->toDateString();
    }

    public function closeModal(): void
    {
        Flux::modal('create-activity-session-modal')->close();
    }

    #[On('open-create-activity-session')]
    public function loadForm(?int $dayIndex = null, ?string $date = null, ?int $activityId = null): void
    {
        $this->resetValidation();

        if ($date) {
            $this->starts_at_date = $date;
            $this->ends_at_date = $date;
        } else {
            $this->starts_at_date = now()->toDateString();
            $this->ends_at_date = null;
        }

        if ($dayIndex !== null && in_array((int) $dayIndex, [0, 1, 2, 3, 4, 5, 6], true)) {
            $this->day_of_week = (int) $dayIndex;
        } else {
            $this->day_of_week = 0;
        }

        if ($activityId !== null) {
            $this->activity_id = (string) $activityId;
        }

        Flux::modal('create-activity-session-modal')->show();
    }

    public function save(): void
    {
        $this->validate();

        if (ActivitySession::hasOverlap(
            (int) $this->activity_id,
            (int) $this->day_of_week,
            $this->starts_at,
            (int) $this->duration_minutes
        )) {
            $this->addError('starts_at', __('Time conflict — another session overlaps this day and time.'));

            return;
        }

        try {
            Log::info('Creating new activity session', [
                'activity_id' => $this->activity_id,
                'day' => $this->day_of_week,
                'starts_at' => $this->starts_at,
            ]);

            ActivitySession::create([
                'activity_id' => $this->activity_id,
                'day_of_week' => $this->day_of_week,
                'starts_at' => $this->starts_at.':00',
                'starts_at_date' => $this->starts_at_date,
                'ends_at_date' => $this->ends_at_date ?: null,
                'duration_minutes' => $this->duration_minutes,
            ]);

            $this->dispatch('activity-session-created');
            $this->reset(['activity_id', 'starts_at', 'duration_minutes', 'ends_at_date']);

            Flux::modal('create-activity-session-modal')->close();
            $this->dispatch('toast', message: __('Activity session created successfully!'), type: 'success');
        } catch (\Exception $e) {
            Log::error('Activity session creation failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: __('Failed to create session.'), type: 'danger');
        }
    }

    public function render()
    {
        return view('livewire.admin.activities.create-activity-session-form');
    }
}
