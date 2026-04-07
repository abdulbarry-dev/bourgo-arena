<?php

namespace App\Livewire\Admin\Plans;

use App\Models\Course;
use App\Models\Plan;
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

    public string $name = '';

    public bool $hasAllCourses = false;

    public array $selectedCourses = [];

    public string $price = '';

    public int|string $durationDays = '';

    public string $includedServicesInput = '';

    public bool $isArchived = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedHasAllCourses(): void
    {
        if ($this->hasAllCourses) {
            $this->selectedCourses = [];
        }
    }

    public function sort(string $column): void
    {
        if (! in_array($column, ['name', 'price', 'duration_days', 'is_archived', 'created_at'], true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
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
        $this->reset(['planId', 'price', 'durationDays', 'includedServicesInput', 'isArchived', 'name', 'hasAllCourses', 'selectedCourses']);

        $this->showFlyout = true;
    }

    public function openEditFlyout(int $id): void
    {
        $plan = Plan::query()->with('courses')->findOrFail($id);

        $this->authorize('update', $plan);

        $this->resetValidation();
        $this->planId = $plan->id;
        $this->name = $plan->name ?: '';
        $this->price = number_format((float) $plan->price, 3, '.', '');
        $this->durationDays = $plan->duration_days;
        $this->includedServicesInput = implode(', ', $plan->included_services ?? []);
        $this->isArchived = $plan->is_archived;

        $this->hasAllCourses = $plan->has_all_courses;
        $this->selectedCourses = $plan->courses->pluck('id')->map(fn ($id) => (string) $id)->toArray();

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
        try {
            $rules = $this->rules();
            if (! $this->hasAllCourses) {
                $rules['selectedCourses'] = ['nullable', 'array'];
                $rules['selectedCourses.*'] = ['exists:courses,id'];
            }
            $validated = $this->validate($rules);

            $payload = [
                'name' => $validated['name'],
                'price' => $validated['price'],
                'duration_days' => (int) $validated['durationDays'],
                'included_services' => $this->normalizedServices($validated['includedServicesInput']),
                'has_all_courses' => (bool) $validated['hasAllCourses'],
                'is_archived' => (bool) $validated['isArchived'],
            ];

            if ($this->planId === null) {
                $this->authorize('create', Plan::class);

                $plan = Plan::query()->create($payload);

                if (! $plan->has_all_courses && ! empty($this->selectedCourses)) {
                    $plan->courses()->sync($this->selectedCourses);
                }

                $this->showFlyout = false;
                $this->dispatch('toast', message: 'Plan created successfully.', type: 'success');

                return;
            }

            $plan = Plan::query()->findOrFail($this->planId);

            $this->authorize('update', $plan);

            $plan->fill($payload);

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
                $this->dispatch('toast', message: 'No changes detected for this plan.', type: 'info');

                return;
            }

            $this->showFlyout = false;
            $this->dispatch('toast', message: 'Plan updated successfully.', type: 'success');

        } catch (Throwable $exception) {
            report($exception);

            if (! $exception instanceof ValidationException) {
                $this->addError('save', 'Plan could not be saved right now. Please try again.');
                $this->dispatch('toast', message: 'Plan save failed. Please review the form and try again.', type: 'danger');
            } else {
                throw $exception;
            }
        }
    }

    protected function rules(): array
    {
        return [
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
            'durationDays' => [
                'required',
                'integer',
                'min:1',
            ],
            'includedServicesInput' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'hasAllCourses' => [
                'boolean',
            ],
            'isArchived' => [
                'boolean',
            ],
        ];
    }

    private function normalizedServices(?string $rawServices): array
    {
        if ($rawServices === null || trim($rawServices) === '') {
            return [];
        }

        $services = preg_split('/[\n,]+/', $rawServices);

        if ($services === false) {
            return [];
        }

        return collect($services)
            ->map(fn (string $service): string => strtolower(trim($service)))
            ->filter(fn (string $service): bool => $service !== '')
            ->unique()
            ->values()
            ->all();
    }

    #[Computed]
    public function plans(): LengthAwarePaginator
    {
        $this->authorize('viewAny', Plan::class);

        return Plan::query()
            ->when($this->search !== '', function (Builder $query): void {
                $query->where('name', 'like', "%{$this->search}%");
            })
            ->when($this->statusFilter === 'active', function (Builder $query): void {
                $query->where('is_archived', false);
            })
            ->when($this->statusFilter === 'archived', function (Builder $query): void {
                $query->where('is_archived', true);
            })
            ->withCount('subscriptions', 'courses')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
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

        return Plan::with(['courses'])->withCount('subscriptions')->find($this->detailPlanId);
    }

    public function render(): View
    {
        return view('livewire.admin.plans.plan-table');
    }
}
