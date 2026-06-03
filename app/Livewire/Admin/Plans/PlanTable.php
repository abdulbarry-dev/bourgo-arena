<?php

namespace App\Livewire\Admin\Plans;

use App\Models\Booking;
use App\Models\Course;
use App\Models\Plan;
use App\Models\Reservation;
use App\Models\Scopes\ActivePlanScope;
use App\Models\Service;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class PlanTable extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'active';

    public int $perPage = 10;

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    // Form flyout properties
    public bool $showFlyout = false;

    public bool $showDetailFlyout = false;

    public ?int $detailPlanId = null;

    public ?int $planId = null;

    public ?int $serviceId = null;

    public string $name = '';

    public bool $hasAllCourses = false;

    public bool $isFacilityOnly = false;

    public array $selectedCourses = [];

    public string $courseToAdd = '';

    public string $price = '';

    public int|string $durationDays = '';

    // isArchived is now managed by actions, not directly in the form
    // public bool $isArchived = false; // Removed from direct form control

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedIsFacilityOnly(): void
    {
        if ($this->isFacilityOnly) {
            $this->hasAllCourses = false;
            $this->selectedCourses = [];
            $this->courseToAdd = '';
        }
    }

    public function updatedHasAllCourses(): void
    {
        if ($this->hasAllCourses) {
            $this->selectedCourses = [];
            $this->courseToAdd = '';
        }
    }

    public function updatedCourseToAdd(): void
    {
        if ($this->courseToAdd === '') {
            return;
        }

        $courseId = (string) $this->courseToAdd;

        if (! in_array($courseId, $this->selectedCourses, true)) {
            $this->selectedCourses[] = $courseId;
        }

        $this->courseToAdd = '';
    }

    public function removeCourse(string $courseId): void
    {
        $this->selectedCourses = array_values(
            array_filter($this->selectedCourses, fn ($id) => $id !== $courseId),
        );
    }

    public function sort(string $column): void
    {
        if (
            ! in_array(
                $column,
                ['name', 'price', 'duration_days', 'created_at'], // Removed 'is_archived'
                true,
            )
        ) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection =
                $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function openCreateFlyout(): void
    {
        $this->authorize('create', Plan::class);

        $this->resetValidation();
        $this->reset([
            'planId',
            'serviceId',
            'price',
            'durationDays',
            // 'isArchived', // Removed
            'name',
            'isFacilityOnly',
            'hasAllCourses',
            'selectedCourses',
            'courseToAdd',
        ]);

        // Pre-select the first available active service if any exist
        $firstAvailableService = $this->availableServices->first();
        if ($firstAvailableService) {
            $this->serviceId = $firstAvailableService->id;
        }

        $this->showFlyout = true;
    }

    public function openEditFlyout(int $id): void
    {
        $plan = Plan::query()->with('courses')->findOrFail($id);

        $this->authorize('update', $plan);

        $this->resetValidation();
        $this->planId = $plan->id;
        $this->serviceId = $plan->service_id;
        $this->name = $plan->name ?: '';
        $this->price = number_format((float) $plan->price, 3, '.', '');
        $this->durationDays = $plan->duration_days;
        // $this->isArchived = $plan->is_archived; // Removed from direct form control

        $this->hasAllCourses = $plan->has_all_courses;
        $this->selectedCourses = $plan->courses
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();
        $this->isFacilityOnly =
            ! $plan->has_all_courses && empty($this->selectedCourses);

        $this->showFlyout = true;
    }

    public function openDetailFlyout(int $id): void
    {
        $plan = Plan::query()->findOrFail($id);

        $this->authorize('view', $plan);

        $this->detailPlanId = $plan->id;
        $this->showDetailFlyout = true;
    }

    public function save(): void
    {
        if ($this->isFacilityOnly) {
            $this->hasAllCourses = false;
            $this->selectedCourses = [];
        }

        try {
            $validated = $this->validate($this->rules());

            $payload = [
                'service_id' => $validated['serviceId'],
                'name' => $validated['name'],
                'price' => $validated['price'],
                'duration_days' => (int) $validated['durationDays'],
                'has_all_courses' => (bool) $validated['hasAllCourses'],
            ];

            if ($this->planId === null) {
                $this->authorize('create', Plan::class);

                $plan = Plan::query()->create($payload + ['is_archived' => false]);

                if (! $plan->has_all_courses && ! empty($this->selectedCourses)) {
                    $plan->courses()->sync($this->selectedCourses);
                }

                $this->showFlyout = false;
                $this->dispatch(
                    'toast',
                    message: __('Plan created successfully.'),
                    type: 'success',
                );

                return;
            }

            $plan = Plan::query()->findOrFail($this->planId);

            $this->authorize('update', $plan);

            $plan->fill($payload); // isArchived is no longer in payload, so it won't be filled here.

            $dirty = $plan->isDirty();

            $plan->save();

            // Handle sync for relations
            if ($plan->has_all_courses) {
                $syncDirty = ! empty($plan->courses()->pluck('id')->toArray());
                $plan->courses()->sync([]);
            } else {
                $currentCourses = $plan->courses()->pluck('id')->toArray();
                $newCourses = array_map('intval', $this->selectedCourses ?? []);

                sort($currentCourses);
                sort($newCourses);

                $syncDirty = $currentCourses !== $newCourses;

                if ($syncDirty) {
                    $plan->courses()->sync($newCourses);
                }
            }

            if (! $dirty && ! $syncDirty) {
                $this->showFlyout = false;
                $this->dispatch(
                    'toast',
                    message: __('No changes detected for this plan.'),
                    type: 'info',
                );

                return;
            }

            $this->showFlyout = false;
            $this->dispatch(
                'toast',
                message: __('Plan updated successfully.'),
                type: 'success',
            );
        } catch (Throwable $exception) {
            report($exception);

            if (! $exception instanceof ValidationException) {
                $this->addError(
                    'save',
                    __('Plan could not be saved right now. Please try again.'),
                );
                $this->dispatch(
                    'toast',
                    message: __(
                        'Plan save failed. Please review the form and try again.',
                    ),
                    type: 'danger',
                );
            } else {
                throw $exception;
            }
        }
    }

    public function archivePlan(int $planId): void
    {
        $plan = Plan::query()->findOrFail($planId);
        $this->authorize('archive', $plan);

        if ($this->hasActiveReferences($plan)) {
            $this->dispatch(
                'toast',
                message: __('Cannot archive plan: it has active subscriptions, bookings, or reservations.'),
                type: 'danger',
            );

            return;
        }

        $plan->is_archived = true;
        $plan->save();

        $this->dispatch('toast', message: __('Plan archived successfully.'), type: 'success');
    }

    public function reactivatePlan(int $planId): void
    {
        $plan = Plan::query()->findOrFail($planId);
        $this->authorize('reactivate', $plan);

        $plan->is_archived = false;
        $plan->save();

        $this->dispatch('toast', message: __('Plan reactivated successfully.'), type: 'success');
    }

    public function deletePlan(int $planId): void
    {
        $plan = Plan::query()->findOrFail($planId);
        $this->authorize('delete', $plan);

        if ($this->hasAnyReferences($plan)) {
            $this->dispatch(
                'toast',
                message: __('Cannot delete plan: it has existing subscriptions, bookings, or reservations.'),
                type: 'danger',
            );

            return;
        }

        $plan->delete();

        $this->dispatch('toast', message: __('Plan deleted successfully.'), type: 'success');
    }

    private function hasActiveReferences(Plan $plan): bool
    {
        // 1. Active subscriptions referencing this plan
        if (Subscription::where('plan_id', $plan->id)->active()->exists()) {
            return true;
        }

        // 2. Active bookings referencing this plan's courses
        // Check for future bookings that are not cancelled
        if (
            Booking::whereHas('courseSession.course.plans', function ($query) use ($plan) {
                $query->where('plans.id', $plan->id);
            })
                ->where('date', '>=', now()->toDateString())
                ->where('status', '!=', 'cancelled')
                ->exists()
        ) {
            return true;
        }

        // 3. Active reservations referencing this plan's activities (via services)
        // Check for future or ongoing reservations that are not cancelled/completed and paid
        if (
            Reservation::whereHas('timeSlot.activity.service', function ($query) use ($plan) {
                $query->where('id', $plan->service_id);
            })
                ->where(function ($query) {
                    $query->where('reservation_status', '!=', 'cancelled')
                        ->where('reservation_status', '!=', 'completed');
                })
                ->where('payment_status', 'paid')
                ->whereHas('timeSlot', fn ($q) => $q->where('end_time', '>=', now()->toTimeString()))
                ->exists()
        ) {
            return true;
        }

        return false;
    }

    private function hasAnyReferences(Plan $plan): bool
    {
        // 1. Any subscriptions referencing this plan (active or inactive)
        if (Subscription::where('plan_id', $plan->id)->exists()) {
            return true;
        }

        // 2. Any bookings referencing this plan's courses (active or inactive)
        if (
            Booking::whereHas('courseSession.course.plans', function ($query) use ($plan) {
                $query->where('plans.id', $plan->id);
            })->exists()
        ) {
            return true;
        }

        // 3. Any reservations referencing this plan's activities (via services)
        if (
            Reservation::whereHas('timeSlot.activity.service', function ($query) use ($plan) {
                $query->where('id', $plan->service_id);
            })->exists()
        ) {
            return true;
        }

        return false;
    }

    protected function rules(): array
    {
        return [
            'serviceId' => ['required', 'integer', 'exists:services,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('plans', 'name')->ignore($this->planId),
            ],
            'price' => [
                'required',
                'numeric',
                'min:0.001',
                'regex:/^\d+(\.\d{1,3})?$/',
            ],
            'durationDays' => ['required', 'integer', 'min:1'],
            'hasAllCourses' => ['boolean'],
            'selectedCourses' => [
                Rule::requiredIf(fn () => ! $this->hasAllCourses && ! $this->isFacilityOnly),
                'array',
            ],
            'selectedCourses.*' => [
                'exists:courses,id',
            ],
        ];
    }

    #[Computed]
    public function plans(): LengthAwarePaginator
    {
        $this->authorize('viewAny', Plan::class);

        return Plan::query()
            ->withoutGlobalScope(ActivePlanScope::class)
            ->when($this->statusFilter === 'archived', function (Builder $query): void {
                $query->where('is_archived', true);
            })
            ->when($this->statusFilter === 'active', function (Builder $query): void {
                $query->where('is_archived', false)
                    ->whereHas('service', function ($q) {
                        $q->where('status', 'active');
                    });
            })
            ->when($this->search !== '', function (Builder $query): void {
                $query->where('name', 'like', "%{$this->search}%");
            })
            ->withCount('subscriptions', 'courses')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function availableServices(): Collection
    {
        return Service::query()->active()->orderBy('name')->get();
    }

    #[Computed]
    public function availableCourses(): Collection
    {
        return Course::orderBy('name')->get();
    }

    #[Computed]
    public function detailPlan(): ?Plan
    {
        if ($this->detailPlanId === null) {
            return null;
        }

        return Plan::with(['courses'])
            ->withCount('subscriptions')
            ->find($this->detailPlanId);
    }

    public function render(): View
    {
        return view('livewire.admin.plans.plan-table');
    }
}
