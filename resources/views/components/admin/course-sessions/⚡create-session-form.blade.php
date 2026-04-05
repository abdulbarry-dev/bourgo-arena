<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\CourseSession;

new class extends Component
{
    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|string|max:255')]
    public $instructor = '';

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
        if ($dayIndex !== null && in_array((int)$dayIndex, [0, 1, 2, 3, 4, 5, 6], true)) {
            $this->day_of_week = (int)$dayIndex;
        } else {
            $this->day_of_week = 0; // Monday default
        }
    }

    public function save()
    {
        $this->validate();

        CourseSession::create([
            'name' => $this->name,
            'instructor' => $this->instructor,
            'day_of_week' => $this->day_of_week,
            'starts_at' => $this->starts_at . ':00',
            'duration_minutes' => $this->duration_minutes,
            'capacity' => $this->capacity,
        ]);

        $this->dispatch('course-session-created');
        $this->reset(['name', 'instructor', 'starts_at', 'duration_minutes', 'capacity']);
        
        \Flux\Flux::modal('create-course-session')->close();
        $this->dispatch('toast', message: 'Course schedule added successfully!', type: 'success');
    }
};
?>

<flux:modal name="create-course-session" class="max-w-md w-full">
    <form wire:submit.prevent="save" class="space-y-6">
        <div>
            <flux:heading size="lg">Add New Course Session</flux:heading>
            <flux:subheading>Create a recurring class template.</flux:subheading>
        </div>

        <div class="space-y-4">
            <flux:input wire:model="name" label="Course Name" placeholder="e.g., Advanced Yoga" required />
            <flux:input wire:model="instructor" label="Instructor" required />

            <flux:radio.group wire:model="day_of_week" label="Day of Week" required>
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <flux:radio value="0" label="Monday" />
                    <flux:radio value="1" label="Tuesday" />
                    <flux:radio value="2" label="Wednesday" />
                    <flux:radio value="3" label="Thursday" />
                    <flux:radio value="4" label="Friday" />
                    <flux:radio value="5" label="Saturday" />
                    <flux:radio value="6" label="Sunday" />
                </div>
            </flux:radio.group>

            <div class="grid grid-cols-2 gap-4">
                <flux:input type="time" wire:model="starts_at" label="Starts At" required />
                <flux:input type="number" wire:model="duration_minutes" label="Duration (mins)" required />
            </div>

            <flux:input type="number" wire:model="capacity" label="Capacity (Spots)" required />
        </div>

        <div class="flex justify-end space-x-2 mt-4">
            <flux:button variant="ghost" x-on:click="$flux.modal('create-course-session').close()">Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save Course</flux:button>
        </div>
    </form>
</flux:modal>
