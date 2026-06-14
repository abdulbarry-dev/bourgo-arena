<?php

namespace App\Livewire\Admin\Courses;

use App\Livewire\Concerns\HasFilters;
use App\Models\Course;
use App\Models\Service;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class CourseManager extends Component
{
    use HasFilters;
    use WithFileUploads;
    use WithPagination;

    #[Validate('required|integer|exists:services,id')]
    public $serviceId = null;

    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|string|max:1000')]
    public $description = '';

    #[Validate('nullable|image|max:2048')]
    public $image;

    public $images = []; // Existing image paths

    public $newImages = []; // Persistent collection of new uploads (temporary files)

    public $uploadQueue = []; // Temporary target for the latest file selection

    public $imageToDeleteIndex = null;

    public $isNewImageDeletion = false;

    public $existingImageUrl = null;

    public $editingCourseId = null;

    public ?Course $viewingCourse = null;

    public $search = '';

    public $statusFilter = '';

    public $hasSessionsFilter = 'all';

    public $serviceFilter = '';

    public $deletingCourseId = null;

    public $isModalOpen = false;

    public $showViewFlyout = false;

    public $status = 'active';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedHasSessionsFilter()
    {
        $this->resetPage();
    }

    public function updatedServiceFilter()
    {
        $this->resetPage();
    }

    public function updatedUploadQueue()
    {
        $this->validate([
            'uploadQueue.*' => 'image|max:2048', // 2MB Max
        ]);

        foreach ($this->uploadQueue as $file) {
            if (count($this->images) + count($this->newImages) < 3) {
                $this->newImages[] = $file;
            } else {
                $this->dispatch('toast', message: __('Maximum of 3 images allowed.'), type: 'danger');
                break;
            }
        }

        $this->dispatch('clear-upload-queue');
    }

    #[On('clear-upload-queue')]
    public function clearUploadQueue()
    {
        $this->uploadQueue = [];
    }

    public function confirmImageDeletion($index, $isNew = false)
    {
        $this->imageToDeleteIndex = $index;
        $this->isNewImageDeletion = $isNew;
        Flux::modal('confirm-image-delete')->show();
    }

    public function executeImageDeletion()
    {
        if ($this->isNewImageDeletion) {
            array_splice($this->newImages, $this->imageToDeleteIndex, 1);
        } else {
            array_splice($this->images, $this->imageToDeleteIndex, 1);
        }

        $this->closeImageDeleteModal();
    }

    public function closeImageDeleteModal()
    {
        Flux::modal('confirm-image-delete')->close();
        $this->imageToDeleteIndex = null;
        $this->isNewImageDeletion = false;
    }

    public function clearNewImages()
    {
        $this->newImages = [];
        $this->uploadQueue = [];
    }

    #[On('open-course-view')]
    public function openViewFlyout($id): void
    {
        $this->viewingCourse = Course::with(['service', 'sessions'])->findOrFail($id);
        $this->showViewFlyout = true;
    }

    public function archive($id)
    {
        $course = Course::findOrFail($id);
        $course->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);

        $this->dispatch('toast', message: __('Course archived successfully.'), type: 'success');
    }

    public function restore($id)
    {
        $course = Course::findOrFail($id);
        $course->update([
            'status' => 'active',
            'archived_at' => null,
        ]);

        $this->dispatch('toast', message: __('Course restored to active status.'), type: 'success');
    }

    #[Computed]
    public function courses()
    {
        $query = Course::query();

        $query = $this->applySearchFilter($query, $this->search, ['name']);

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->serviceFilter) {
            $query->where('service_id', $this->serviceFilter);
        }

        $query = $this->applyRelationPresenceFilter($query, 'sessions', $this->hasSessionsFilter);

        return $query->withCount('sessions')->orderBy('name')->paginate(6);
    }

    public function updatedServiceId($value)
    {
        $this->serviceId = (int) $value;
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->status = 'active';

        // Pre-select the first available active service if any exist
        $firstAvailableService = $this->availableServices->first();
        if ($firstAvailableService) {
            $this->serviceId = $firstAvailableService->id;
        }

        $this->isModalOpen = true;
        Flux::modal('course-form-modal')->show();
    }

    public function openEditModal($id)
    {
        $this->resetForm();
        $course = Course::findOrFail($id);
        $this->editingCourseId = $course->id;
        $this->serviceId = $course->service_id;
        $this->name = $course->name;
        $this->description = $course->description;
        $this->status = $course->status;

        $this->images = is_array($course->images) ? $course->images : ($course->image_url ? [$course->image_url] : []);
        $this->newImages = [];
        $this->uploadQueue = [];

        $this->isModalOpen = true;
        $this->showViewFlyout = false;
        Flux::modal('course-form-modal')->show();
    }

    public function save()
    {
        $this->validate();

        try {
            $imagePaths = $this->images;
            foreach ($this->newImages as $img) {
                $imagePaths[] = $img->store('courses', 'public');
            }

            $payload = [
                'service_id' => $this->serviceId,
                'name' => $this->name,
                'description' => $this->description,
                'status' => $this->status,
                'archived_at' => $this->status === 'archived' ? now() : null,
                'images' => $imagePaths,
                'image_url' => count($imagePaths) > 0 ? $imagePaths[0] : null,
            ];

            if ($this->editingCourseId) {
                $course = Course::findOrFail($this->editingCourseId);
                $course->update($payload);
                $this->dispatch('toast', message: __('Course updated successfully!'), type: 'success');
            } else {
                Course::create($payload);
                $this->dispatch('toast', message: __('Course created successfully!'), type: 'success');
            }

            $this->closeModal();
        } catch (\Exception $e) {
            Log::error('Failed to save course', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: __('Failed to save course.'), type: 'danger');
        }
    }

    public function confirmDelete($id)
    {
        $this->deletingCourseId = $id;
        $this->showViewFlyout = false;
        Flux::modal('delete-course-modal')->show();
    }

    public function delete()
    {
        if (! $this->deletingCourseId) {
            return;
        }

        try {
            $course = Course::findOrFail($this->deletingCourseId);

            if ($course->sessions()->count() > 0) {
                $this->dispatch('toast', message: __('Cannot delete course with active sessions.'), type: 'danger');
                $this->closeDeleteModal();

                return;
            }

            $course->delete();
            $this->dispatch('toast', message: __('Course deleted successfully.'), type: 'success');

            $this->closeDeleteModal();
        } catch (\Exception $e) {
            Log::error('Failed to delete course', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: __('Failed to delete course.'), type: 'danger');
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

    public function resetForm()
    {
        $this->reset([
            'serviceId',
            'name',
            'description',
            'editingCourseId',
            'image',
            'images',
            'newImages',
            'uploadQueue',
            'imageToDeleteIndex',
            'isNewImageDeletion',
            'existingImageUrl',
            'status',
        ]);
        $this->resetValidation();
        $this->status = 'active';
    }

    #[Computed]
    public function availableServices()
    {
        return Service::query()->active()->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.admin.courses.course-manager');
    }
}
