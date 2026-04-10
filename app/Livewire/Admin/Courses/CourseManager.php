<?php

namespace App\Livewire\Admin\Courses;

use App\Models\Course;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class CourseManager extends Component
{
    use WithFileUploads;

    public $courses;

    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|string|max:255')]
    public $instructor = '';

    #[Validate('nullable|string|max:1000')]
    public $description = '';

    #[Validate('nullable|string|max:7')]
    public $color = '#8b5cf6';

    #[Validate('nullable|image|max:2048')]
    public $image;

    public $existingImageUrl = null;

    public $editingCourseId = null;

    public $viewingCourseId = null;

    public $search = '';

    public $deletingCourseId = null;

    public $isModalOpen = false;

    public function mount()
    {
        $this->loadCourses();
    }

    public function updatedSearch()
    {
        $this->loadCourses();
    }

    public function loadCourses()
    {
        $this->courses = Course::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('instructor', 'like', '%'.$this->search.'%');
            })
            ->orderBy('name')
            ->get();
    }

    public function openViewModal($id)
    {
        $this->viewingCourseId = $id;
        Flux::modal('view-course-modal')->show();
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
        $this->existingImageUrl = $course->image_url;
        $this->image = null;

        $this->isModalOpen = true;

        // Close view modal if it was open
        Flux::modal('view-course-modal')->close();
        Flux::modal('course-form-modal')->show();
    }

    public function save()
    {
        $this->validate();

        $payload = [
            'name' => $this->name,
            'instructor' => $this->instructor,
            'description' => $this->description,
            'color' => $this->color,
        ];

        if ($this->image) {
            $path = $this->image->store('courses', 'public');
            $payload['image_url'] = Storage::url($path);
        }

        if ($this->editingCourseId) {
            $course = Course::findOrFail($this->editingCourseId);
            $course->update($payload);
            $this->dispatch('toast', message: 'Course updated successfully!', type: 'success');
        } else {
            Course::create($payload);
            $this->dispatch('toast', message: 'Course created successfully!', type: 'success');
        }

        $this->closeModal();
        $this->loadCourses();
    }

    public function confirmDelete($id)
    {
        $this->deletingCourseId = $id;
        // Close view modal if it was open
        Flux::modal('view-course-modal')->close();
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
        $this->dispatch('toast', message: 'Course deleted successfully.', type: 'success');

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

    public function closeViewModal()
    {
        $this->viewingCourseId = null;
        Flux::modal('view-course-modal')->close();
    }

    public function resetForm()
    {
        $this->reset(['name', 'instructor', 'description', 'color', 'editingCourseId', 'viewingCourseId', 'image', 'existingImageUrl']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.courses.course-manager', [
            'viewingCourse' => $this->viewingCourseId ? Course::find($this->viewingCourseId) : null,
        ]);
    }
}
