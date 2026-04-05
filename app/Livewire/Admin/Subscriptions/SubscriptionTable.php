<?php

namespace App\Livewire\Admin\Subscriptions;

use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class SubscriptionTable extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $planFilter = null;

    public int $perPage = 10;

    public string $sortBy = 'ends_at';

    public string $sortDirection = 'asc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPlanFilter(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if (! in_array($column, ['member', 'plan', 'status', 'starts_at', 'ends_at'], true)) {
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

    #[On('subscription-created')]
    #[On('subscription-updated')]
    public function refreshTable(): void
    {
        // Trigger a refresh when sibling components mutate subscription data.
    }

    #[Computed]
    public function subscriptions(): LengthAwarePaginator
    {
        $this->authorize('viewAny', Subscription::class);

        return $this->filteredSubscriptionsQuery()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function plans(): Collection
    {
        return Plan::query()
            ->where('is_archived', false)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function exportCsv()
    {
        $subscriptions = $this->filteredSubscriptionsQuery()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="subscriptions.csv"',
        ];

        return response()->stream(function () use ($subscriptions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Member', 'Plan', 'Status', 'Start Date', 'End Date']);

            foreach ($subscriptions as $sub) {
                fputcsv($file, [
                    $sub->member ? $sub->member->name : 'Unknown',
                    $sub->plan ? __($sub->plan->name) : 'Unknown',
                    ucfirst($sub->status),
                    $sub->starts_at ? $sub->starts_at->format('Y-m-d') : '',
                    $sub->ends_at ? $sub->ends_at->format('Y-m-d') : '',
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }

    public function exportPdf()
    {
        $subscriptions = $this->filteredSubscriptionsQuery()->get();

        $pdf = Pdf::loadView('pdf.subscriptions', ['subscriptions' => $subscriptions])
            ->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'subscriptions.pdf');
    }

    public function render(): View
    {
        return view('livewire.admin.subscriptions.subscription-table');
    }

    private function filteredSubscriptionsQuery(): Builder
    {
        $query = Subscription::query()
            ->whereHas('member', function (Builder $query): void {
                $query->whereNull('deleted_at');
            })
            ->when($this->search !== '', function (Builder $query): void {
                $searchTerm = "%{$this->search}%";

                $query->where(function (Builder $builder) use ($searchTerm): void {
                    $builder
                        ->whereHas('member', function (Builder $memberQuery) use ($searchTerm): void {
                            $memberQuery
                                ->where('name', 'like', $searchTerm)
                                ->orWhere('email', 'like', $searchTerm)
                                ->orWhere('phone', 'like', $searchTerm);
                        })
                        ->orWhereHas('plan', function (Builder $planQuery) use ($searchTerm): void {
                            $planQuery->where('name', 'like', $searchTerm);
                        });
                });
            })
            ->when($this->statusFilter !== '', function (Builder $query): void {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->planFilter !== null, function (Builder $query): void {
                $query->where('plan_id', $this->planFilter);
            })
            ->with([
                'member:id,name,email,phone',
                'plan:id,name',
            ]);

        return $this->applySorting($query);
    }

    private function applySorting(Builder $query): Builder
    {
        return match ($this->sortBy) {
            'member' => $query->orderBy(
                Member::query()
                    ->select('name')
                    ->whereColumn('members.id', 'subscriptions.member_id')
                    ->limit(1),
                $this->sortDirection,
            ),
            'plan' => $query->orderBy(
                Plan::query()
                    ->select('name')
                    ->whereColumn('plans.id', 'subscriptions.plan_id')
                    ->limit(1),
                $this->sortDirection,
            ),
            default => $query->orderBy($this->sortBy, $this->sortDirection),
        };
    }
}
