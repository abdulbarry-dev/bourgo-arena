<?php

namespace App\Livewire\Admin\Members;

use App\Models\Member;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberTable extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $planFilter = null;

    public string $hasActiveSubscription = 'all';

    public int $perPage = 7;

    public bool $selectionEnabled = false;

    public bool $showExportConfirmModal = false;

    public ?int $actionMemberId = null;

    public bool $showSuspendModal = false;

    public bool $showActivateModal = false;

    public bool $showDeleteModal = false;

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public function mount(bool $selectionEnabled = false): void
    {
        $this->selectionEnabled = $selectionEnabled;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedHasActiveSubscription(): void
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

    public function selectMember(int $memberId): void
    {
        if (! $this->selectionEnabled) {
            return;
        }

        session(['members.selected_member_id' => $memberId]);

        $this->dispatch('member-selected', memberId: $memberId);
    }

    public function sort(string $column): void
    {
        if (! in_array($column, ['name', 'email', 'phone', 'status', 'plan'], true)) {
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

    #[On('member-updated')]
    public function refreshTable(): void
    {
        // Trigger a refresh when sibling components mutate member data.
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('viewAny', Member::class);

        $membersQuery = $this->filteredMembersQuery()
            ->with(['activeSubscription.plan'])
            ->orderBy('id');

        return response()->streamDownload(function () use ($membersQuery): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fputcsv($output, ['Name', 'Email', 'Phone', 'Status', 'Plan']);

            $membersQuery->chunkById(200, function (Collection $members) use ($output): void {
                foreach ($members as $member) {
                    fputcsv($output, [
                        $member->name,
                        $member->email,
                        $member->phone,
                        $member->status,
                        $member->activeSubscription?->plan?->name ?? 'No active plan',
                    ]);
                }
            });

            fclose($output);
        }, 'members.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function openExportConfirmModal(): void
    {
        $this->showExportConfirmModal = true;
    }

    public function closeExportConfirmModal(): void
    {
        $this->showExportConfirmModal = false;
    }

    public function confirmExport(): StreamedResponse
    {
        $this->closeExportConfirmModal();

        return $this->exportCsv();
    }

    public function confirmSuspend(int $id): void
    {
        $this->actionMemberId = $id;
        $this->showSuspendModal = true;
    }

    public function suspend(): void
    {
        $member = Member::findOrFail($this->actionMemberId);
        $this->authorize('suspend', $member);

        if ($member->status === 'suspended') {
            $this->showSuspendModal = false;
            $this->dispatch('toast', message: 'Member is already suspended.', type: 'info');

            return;
        }

        $member->update(['status' => 'suspended']);

        $this->showSuspendModal = false;
        $this->dispatch('member-updated', memberId: $member->id);
        $this->dispatch('toast', message: 'Member suspended successfully.', type: 'success');
    }

    public function confirmActivate(int $id): void
    {
        $this->actionMemberId = $id;
        $this->showActivateModal = true;
    }

    public function activate(): void
    {
        $member = Member::findOrFail($this->actionMemberId);
        $this->authorize('activate', $member);

        if ($member->status === 'active') {
            $this->showActivateModal = false;
            $this->dispatch('toast', message: 'Member is already active.', type: 'info');

            return;
        }

        $member->update(['status' => 'active']);

        $this->showActivateModal = false;
        $this->dispatch('member-updated', memberId: $member->id);
        $this->dispatch('toast', message: 'Member activated successfully.', type: 'success');
    }

    public function confirmDelete(int $id): void
    {
        $this->actionMemberId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $member = Member::findOrFail($this->actionMemberId);
        $this->authorize('delete', $member);

        $member->delete();

        if (session('members.selected_member_id') === $this->actionMemberId) {
            session()->forget('members.selected_member_id');
        }

        $this->showDeleteModal = false;
        $this->dispatch('member-updated', memberId: $this->actionMemberId);
        $this->dispatch('toast', message: 'Member deleted successfully.', type: 'success');
    }

    #[Computed]
    public function members(): LengthAwarePaginator
    {
        $this->authorize('viewAny', Member::class);

        return $this->filteredMembersQuery()
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

    public function render(): View
    {
        return view('livewire.admin.members.member-table');
    }

    private function filteredMembersQuery(): Builder
    {
        $query = Member::query()
            ->searchable($this->search)
            ->when($this->statusFilter !== '', function (Builder $query): void {
                $query->byStatus($this->statusFilter);
            })
            ->when($this->planFilter !== null, function (Builder $query): void {
                $query->byPlan($this->planFilter);
            })
            ->when($this->hasActiveSubscription !== 'all', function (Builder $query): void {
                if ($this->hasActiveSubscription === 'with') {
                    $query->whereHas('activeSubscription');
                } else {
                    $query->whereDoesntHave('activeSubscription');
                }
            })
            ->withDetails()
            ->with('activeSubscription.plan');

        return $this->applySorting($query);
    }

    private function applySorting(Builder $query): Builder
    {
        return match ($this->sortBy) {
            'plan' => $query->orderBy(
                Plan::query()
                    ->select('name')
                    ->join('subscriptions', 'plans.id', '=', 'subscriptions.plan_id')
                    ->whereColumn('subscriptions.member_id', 'members.id')
                    ->where('subscriptions.status', 'active')
                    ->whereDate('subscriptions.ends_at', '>', now())
                    ->limit(1),
                $this->sortDirection,
            ),
            default => $query->orderBy($this->sortBy, $this->sortDirection),
        };
    }
}
