<?php

namespace App\Livewire\Admin\Plans;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public ?int $planId = null;

    public array $name = ['en' => '', 'fr' => ''];

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
        $this->reset(['planId', 'price', 'durationDays', 'includedServicesInput', 'isArchived']);
        $this->name = ['en' => '', 'fr' => ''];

        $this->showFlyout = true;
    }

    public function openEditFlyout(int $id): void
    {
        $plan = Plan::query()->findOrFail($id);

        $this->authorize('update', $plan);

        $this->resetValidation();
        $this->planId = $plan->id;
        $this->name = [
            'en' => $plan->getTranslation('name', 'en', false) ?: '',
            'fr' => $plan->getTranslation('name', 'fr', false) ?: '',
        ];
        $this->price = number_format((float) $plan->price, 3, '.', '');
        $this->durationDays = $plan->duration_days;
        $this->includedServicesInput = implode(', ', $plan->included_services ?? []);
        $this->isArchived = $plan->is_archived;

        $this->showFlyout = true;
    }

    public function save(): void
    {
        try {
            $validated = $this->validate($this->rules());

            $payload = [
                'name' => $validated['name'],
                'price' => $validated['price'],
                'duration_days' => (int) $validated['durationDays'],
                'included_services' => $this->normalizedServices($validated['includedServicesInput']),
                'is_archived' => (bool) $validated['isArchived'],
            ];

            if ($this->planId === null) {
                $this->authorize('create', Plan::class);

                Plan::query()->create($payload);

                $this->showFlyout = false;
                $this->dispatch('toast', message: 'Plan created successfully.', type: 'success');

                return;
            }

            $plan = Plan::query()->findOrFail($this->planId);

            $this->authorize('update', $plan);

            $plan->fill($payload);

            if (! $plan->isDirty()) {
                $this->showFlyout = false;
                $this->dispatch('toast', message: 'No changes detected for this plan.', type: 'info');

                return;
            }

            $plan->save();

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

    public function delete(): void
    {
        if ($this->planId === null) {
            return;
        }

        $plan = Plan::query()->findOrFail($this->planId);

        $this->authorize('delete', $plan);

        if ($plan->subscriptions()->exists()) {
            $this->dispatch('toast', message: 'Plan cannot be deleted because subscriptions are linked to it.', type: 'info');

            return;
        }

        $plan->delete();

        $this->showFlyout = false;
        $this->dispatch('toast', message: 'Plan deleted successfully.', type: 'success');
    }

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'array',
            ],
            'name.en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('plans', 'name->en')->ignore($this->planId),
            ],
            'name.fr' => [
                'required',
                'string',
                'max:255',
                Rule::unique('plans', 'name->fr')->ignore($this->planId),
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
                $query->where('name->' . app()->getLocale(), 'like', "%{$this->search}%");
            })
            ->when($this->statusFilter === 'active', function (Builder $query): void {
                $query->where('is_archived', false);
            })
            ->when($this->statusFilter === 'archived', function (Builder $query): void {
                $query->where('is_archived', true);
            })
            ->withCount('subscriptions')
            ->when($this->sortBy === 'name', function (Builder $query): void {
                $query->orderBy('name->' . app()->getLocale(), $this->sortDirection);
            }, function (Builder $query): void {
                $query->orderBy($this->sortBy, $this->sortDirection);
            })
            ->paginate($this->perPage);
    }

    public function render(): View
    {
        return view('livewire.admin.plans.plan-table');
    }
}
