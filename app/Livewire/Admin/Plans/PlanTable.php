<?php

namespace App\Livewire\Admin\Plans;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class PlanTable extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'active';

    public int $perPage = 25;

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

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
            ->withCount('subscriptions')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render(): View
    {
        return view('livewire.admin.plans.plan-table');
    }
}
