<?php

namespace App\Livewire\Admin\CourseSessions;

use App\Models\Course;
use App\Models\CourseSession;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CreateSessionForm extends Component
{
    #[Validate('required|integer|exists:courses,id')]
    public $course_id = '';

    #[Validate('required|integer|min:0|max:6')]
    public $day_of_week = 0; // 0 = Monday

    #[Validate('required|string')]
    public $starts_at = '12:00';

    #[Validate('required|integer|min:15')]
    public $duration_minutes = 60;

    #[Validate('required|integer|min:1')]
    public $capacity = 10;

    #[Validate('required|date')]
    public $starts_at_date;

    #[Validate('nullable|date|after_or_equal:starts_at_date')]
    public $ends_at_date;

    public function mount()
    {
        $this->starts_at_date = now()->toDateString();
    }

    #[On('open-create-course-session')]
    public function loadForm($dayIndex = null, $date = null)
    {
        $this->resetValidation();

        if ($date) {
            $this->starts_at_date = $date;
        } else {
            $this->starts_at_date = now()->toDateString();
        }

        if ($dayIndex !== null && in_array((int) $dayIndex, [0, 1, 2, 3, 4, 5, 6], true)) {
            $this->day_of_week = (int) $dayIndex;
        } else {
            $this->day_of_week = 0; // Monday default
        }

        Flux::modal('create-course-session-modal')->show();
    }

    public function save()
    {
        $this->validate();

        // Check for overlaps
        if (CourseSession::hasOverlap(
            (int) $this->course_id,
            (int) $this->day_of_week,
            $this->starts_at,
            (int) $this->duration_minutes
        )) {
            $this->addError('starts_at', __('This class already has a session scheduled during this time range on this day.'));

            return;
        }

        try {
            Log::info('Creating new course session', [
                'course_id' => $this->course_id,
                'day' => $this->day_of_week,
                'starts_at' => $this->starts_at,
            ]);

            CourseSession::create([
                'course_id' => $this->course_id,
                'day_of_week' => $this->day_of_week,
                'starts_at' => $this->starts_at.':00',
                'starts_at_date' => $this->starts_at_date,
                'ends_at_date' => $this->ends_at_date ?: null,
                'duration_minutes' => $this->duration_minutes,
                'capacity' => $this->capacity,
            ]);

            $this->dispatch('course-session-created');
            $this->reset(['course_id', 'starts_at', 'duration_minutes', 'capacity']);

            Flux::modal('create-course-session-modal')->close();
            $this->dispatch('toast', message: __('Course schedule added successfully!'), type: 'success');
        } catch (\Exception $e) {
            Log::error('Session creation failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: __('Failed to create schedule.'), type: 'danger');
        }
    }

    public function render()
    {
        return view('livewire.admin.course-sessions.create-session-form', [
            'courses' => Course::orderBy('name')->get(),
        ]);
    }
}
