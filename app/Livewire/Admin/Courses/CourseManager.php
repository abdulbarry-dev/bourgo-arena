<?php

namespace App\Livewire\Admin\Courses;

use App\Models\Course;
use Flux\Flux;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CourseManager extends Component
{
    public $courses;

    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|string|max:255')]
    public $instructor = '';

    #[Validate('nullable|string|max:1000')]
    public $description = '';

    #[Validate('nullable|string|max:7')]
    public $color = '#8b5cf6';

    public $editingCourseId = null;

    public $deletingCourseId = null;

    public $isModalOpen = false;

    public function mount()
    {
        $this->loadCourses();
    }

    public function loadCourses()
    {
        $this->courses = Course::orderBy('name')->get();
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
        Flux::modal('course-form-modal')->show();
    }

    public function openEditModal($id)
    {
        $this->resetForm();
        $course = Course::findOrFail($id);
        $this->editingCourseId = $course->id;
        $this->name = $course->name;
        $this->instructor = $course->instructor;
        $this->description = $course->description;
        $this->color = $course->color ?? '#9ca3af';

        $this->isModalOpen = true;
        Flux::modal('course-form-modal')->show();
    }

    public function save()
    {
        $this->validate();

        if ($this->editingCourseId) {
            $course = Course::findOrFail($this->editingCourseId);
            $course->update([
                'name' => $this->name,
                'instructor' => $this->instructor,
                'description' => $this->description,
                'color' => $this->color,
            ]);
            $this->dispatch('toast', message: 'Course updated successfully!', type: 'success');
        } else {
            Course::create([
                'name' => $this->name,
                'instructor' => $this->instructor,
                'description' => $this->description,
                'color' => $this->color,
            ]);
            $this->dispatch('toast', message: 'Course created successfully!', type: 'success');
        }

        $this->closeModal();
        $this->loadCourses();
    }

    public function confirmDelete($id)
    {
        $this->deletingCourseId = $id;
        Flux::modal('delete-course-modal')->show();
    }

    public function delete()
    {
        if (! $this->deletingCourseId) {
            return;
        }

        $course = Course::findOrFail($this->deletingCourseId);

        if ($course->sessions()->count() > 0) {
            $this->dispatch('toast', message: 'Cannot delete course with active sessions.', type: 'danger');
            $this->closeDeleteModal();

            return;
        }

        $course->delete();
        $this->dispatch('toast', message: 'Course deleted successfully.', type: 'info');

        $this->closeDeleteModal();
        $this->loadCourses();
    }

    public function closeDeleteModal()
    {
        $this->deletingCourseId = null;
        Flux::modal('delete-course-modal')->close();
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        Flux::modal('course-form-modal')->close();
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset(['name', 'instructor', 'description', 'color', 'editingCourseId']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.courses.course-manager')->layout('layouts.app');
    }
}
