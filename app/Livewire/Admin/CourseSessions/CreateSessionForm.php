<?php

namespace App\Livewire\Admin\CourseSessions;

use App\Models\CourseSession;
use Flux\Flux;
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

    #[On('open-create-course-session')]
    public function loadForm($dayIndex = null)
    {
        $this->resetValidation();
        if ($dayIndex !== null && in_array((int) $dayIndex, [0, 1, 2, 3, 4, 5, 6], true)) {
            $this->day_of_week = (int) $dayIndex;
        } else {
            $this->day_of_week = 0; // Monday default
        }
    }

    public function save()
    {
        $this->validate();

        CourseSession::create([
            'course_id' => $this->course_id,
            'day_of_week' => $this->day_of_week,
            'starts_at' => $this->starts_at.':00',
            'duration_minutes' => $this->duration_minutes,
            'capacity' => $this->capacity,
        ]);

        $this->dispatch('course-session-created');
        $this->reset(['course_id', 'starts_at', 'duration_minutes', 'capacity']);

        Flux::modal('create-course-session')->close();
        $this->dispatch('toast', message: 'Course schedule added successfully!', type: 'success');
    }

    public function render()
    {
        return view('livewire.admin.course-sessions.create-session-form', [
            'courses' => \App\Models\Course::orderBy('name')->get(),
        ]);
    }
}
