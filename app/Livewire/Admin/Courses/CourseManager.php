<?php

namespace App\Livewire\Admin\Courses;

use App\Livewire\Concerns\HasFilters;
use App\Models\Course;
use App\Models\CourseSession;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class CourseManager extends Component
{
    use HasFilters;
    use WithFileUploads;

    public $courses;

    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|string|max:255')]
    public $instructor = '';

    #[Validate('nullable|string|max:1000')]
    public $description = '';

    #[Validate('nullable|image|max:2048')]
    public $image;

    public $existingImageUrl = null;

    public $editingCourseId = null;

    public $viewingCourseId = null;

    public $search = '';

    public $categoryFilter = '';

    public $instructorFilter = '';

    public $hasSessionsFilter = 'all';

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

    public function updatedCategoryFilter()
    {
        $this->loadCourses();
    }

    public function updatedInstructorFilter()
    {
        $this->loadCourses();
    }

    public function updatedHasSessionsFilter()
    {
        $this->loadCourses();
    }

    public function loadCourses()
    {
        $query = Course::query();

        $query = $this->applySearchFilter($query, $this->search, ['name', 'instructor']);

        if ($this->categoryFilter) {
            $query->where('category', $this->categoryFilter);
        }

        if ($this->instructorFilter) {
            $query->where('instructor', $this->instructorFilter);
        }

        $query = $this->applyRelationPresenceFilter($query, 'sessions', $this->hasSessionsFilter);

        $this->courses = $query->orderBy('name')->get();
    }

    public function openViewModal($id)
    {
        $this->viewingCourseId = $id;
        Flux::modal('view-course-modal')->show();
    }

    public function getCategoriesProperty()
    {
        return Course::query()->select('category')->distinct()->orderBy('category')->pluck('category')->filter()->values();
    }

    public function getInstructorsProperty()
    {
        return Course::query()->select('instructor')->distinct()->orderBy('instructor')->pluck('instructor')->filter()->values();
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

        try {
            Log::info('Saving course', [
                'course_id' => $this->editingCourseId,
                'name' => $this->name,
                'instructor' => $this->instructor,
            ]);

            $payload = [
                'name' => $this->name,
                'instructor' => $this->instructor,
                'description' => $this->description,
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
        } catch (\Exception $e) {
            Log::error('Failed to save course', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('toast', message: 'Failed to save course: '.$e->getMessage(), type: 'danger');
        }
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

        try {
            $course = Course::findOrFail($this->deletingCourseId);
            Log::info('Deleting course', ['course_id' => $course->id, 'name' => $course->name]);

            if ($course->sessions()->count() > 0) {
                Log::warning('Delete blocked: Course has active sessions', ['course_id' => $course->id]);
                $this->dispatch('toast', message: 'Cannot delete course with active sessions.', type: 'danger');
                $this->closeDeleteModal();

                return;
            }

            $course->delete();
            $this->dispatch('toast', message: 'Course deleted successfully.', type: 'success');

            $this->closeDeleteModal();
            $this->loadCourses();
        } catch (\Exception $e) {
            Log::error('Failed to delete course', [
                'course_id' => $this->deletingCourseId,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('toast', message: 'Failed to delete course.', type: 'danger');
        }
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
        $this->reset(['name', 'instructor', 'description', 'editingCourseId', 'viewingCourseId', 'image', 'existingImageUrl', 'editingSessionId', 'deletingSessionId']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.courses.course-manager', [
            'viewingCourse' => $this->viewingCourseId ? Course::with('sessions')->find($this->viewingCourseId) : null,
        ]);
    }
}
