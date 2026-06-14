<?php

namespace App\Livewire\Admin\Subscriptions;

use App\Actions\Subscriptions\ResumeSubscriptionAction;
use App\Actions\Subscriptions\SuspendSubscriptionAction;
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
use Livewire\Component;
use Livewire\WithPagination;

class SubscriptionTable extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $planFilter = null;

    public bool $showExportConfirmModal = false;

    public bool $showSubscriptionPreviewModal = false;

    public string $exportFormat = 'csv';

    public ?int $previewSubscriptionId = null;

    public int $perPage = 10;

    public string $sortBy = 'ends_at';

    public string $sortDirection = 'asc';

    public bool $showSubscriptionEditModal = false;

    public bool $showSubscriptionLifecycleModal = false;

    public bool $showDeleteSubscriptionModal = false;

    public string $subscriptionLifecycleAction = 'suspend';

    public string $suspensionReason = 'medical';

    public ?int $editPlanId = null;

    public string $editStartsAt = '';

    public string $editEndsAt = '';

    public string $editPaymentMethod = 'cash';

    public ?string $editPaymentReference = null;

    public string $editAmountPaid = '';

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

    #[Computed]
    #[On('subscription-created')]
    public function subscriptions(): LengthAwarePaginator
    {
        $this->authorize('viewAny', Subscription::class);

        return $this->filteredSubscriptionsQuery()
            ->paginate($this->perPage);
    }

    public function updatedPlanFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function plans(): Collection
    {
        return Plan::query()
            ->where('is_archived', false)
            ->orderBy('name')
            ->get(['id', 'name', 'is_archived']);
    }

    public function exportCsv()
    {
        abort_unless(auth()->user()->isAdmin(), 403);
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
        abort_unless(auth()->user()->isAdmin(), 403);
        $subscriptions = $this->filteredSubscriptionsQuery()->get();

        $pdf = Pdf::loadView('pdf.subscriptions', ['subscriptions' => $subscriptions])
            ->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'subscriptions.pdf');
    }

    public function openExportConfirmModal(string $format): void
    {
        if (! in_array($format, ['csv', 'pdf'], true)) {
            return;
        }

        $this->exportFormat = $format;
        $this->showExportConfirmModal = true;
    }

    public function closeExportConfirmModal(): void
    {
        $this->showExportConfirmModal = false;
    }

    public function confirmExport()
    {
        $this->showExportConfirmModal = false;

        if ($this->exportFormat === 'pdf') {
            return $this->exportPdf();
        }

        return $this->exportCsv();
    }

    public function openSubscriptionPreview(int $subscriptionId): void
    {
        $this->previewSubscriptionId = $subscriptionId;
        $this->showSubscriptionPreviewModal = true;
        $this->showSubscriptionEditModal = false;
        $this->showSubscriptionLifecycleModal = false;
        $this->showDeleteSubscriptionModal = false;
    }

    public function closeSubscriptionPreviewModal(): void
    {
        $this->resetSubscriptionActionState();
    }

    public function openSubscriptionLifecycleModal(int $subscriptionId, string $action): void
    {
        if (! in_array($action, ['suspend', 'resume'], true)) {
            return;
        }

        $subscription = $this->subscriptionForAction($subscriptionId);

        if ($subscription === null) {
            $this->addError('previewSubscriptionId', __('The selected subscription was not found.'));

            return;
        }

        $this->previewSubscriptionId = $subscription->id;
        $this->subscriptionLifecycleAction = $action;
        $this->showSubscriptionPreviewModal = false;
        $this->showSubscriptionLifecycleModal = true;
        $this->showDeleteSubscriptionModal = false;
    }

    public function closeSubscriptionLifecycleModal(): void
    {
        $this->resetSubscriptionActionState();
    }

    public function confirmSubscriptionLifecycleAction(
        SuspendSubscriptionAction $suspendAction,
        ResumeSubscriptionAction $resumeAction,
    ): void {
        $subscription = $this->previewSubscription;

        if ($subscription === null) {
            $this->addError('previewSubscriptionId', __('The selected subscription was not found.'));

            return;
        }

        if ($this->subscriptionLifecycleAction === 'suspend') {
            $this->authorize('suspend', Subscription::class);

            $suspendAction->execute($subscription, auth()->id());

            $this->dispatch('toast', message: __('Subscription suspended successfully'), type: 'success');
        } else {
            $this->authorize('resume', Subscription::class);

            $resumeAction->execute($subscription, auth()->id());

            $this->dispatch('toast', message: __('Subscription reactivated successfully'), type: 'success');
        }

        $this->dispatch('subscription-updated', subscriptionId: $subscription->id);
        $this->resetSubscriptionActionState();
    }

    public function openDeleteSubscriptionModal(int $subscriptionId): void
    {
        $this->authorize('delete', Subscription::class);

        $subscription = $this->subscriptionForAction($subscriptionId);

        if ($subscription === null) {
            $this->addError('previewSubscriptionId', __('The selected subscription was not found.'));

            return;
        }

        $this->previewSubscriptionId = $subscription->id;
        $this->showSubscriptionPreviewModal = false;
        $this->showSubscriptionEditModal = false;
        $this->showSubscriptionLifecycleModal = false;
        $this->showDeleteSubscriptionModal = true;
    }

    public function closeDeleteSubscriptionModal(): void
    {
        $this->resetSubscriptionActionState();
    }

    public function deleteSubscription(): void
    {
        $this->authorize('delete', Subscription::class);

        $subscription = $this->previewSubscription;

        if ($subscription === null) {
            $this->addError('previewSubscriptionId', __('The selected subscription was not found.'));

            return;
        }

        $subscriptionId = $subscription->id;
        $subscription->delete();

        $this->dispatch('subscription-updated', subscriptionId: $subscriptionId);
        $this->dispatch('toast', message: __('Subscription deleted successfully'), type: 'success');
        $this->resetSubscriptionActionState();
    }

    public function render(): View
    {
        return view('livewire.admin.subscriptions.subscription-table');
    }

    #[Computed]
    public function previewSubscription(): ?Subscription
    {
        if ($this->previewSubscriptionId === null) {
            return null;
        }

        return Subscription::query()
            ->with([
                'member',
                'plan',
                'auditLogs' => function ($query): void {
                    $query->with('performedBy')->limit(5);
                },
            ])
            ->find($this->previewSubscriptionId);
    }

    private function subscriptionForAction(int $subscriptionId): ?Subscription
    {
        return Subscription::query()
            ->with([
                'member',
                'plan',
                'auditLogs' => function ($query): void {
                    $query->with('performedBy')->limit(5);
                },
            ])
            ->find($subscriptionId);
    }

    private function resetSubscriptionActionState(): void
    {
        $this->previewSubscriptionId = null;
        $this->showSubscriptionPreviewModal = false;
        $this->showSubscriptionLifecycleModal = false;
        $this->showDeleteSubscriptionModal = false;
        $this->subscriptionLifecycleAction = 'suspend';
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

    public function openCreateSubscriptionFlyout(): void
    {
        $this->dispatch('open-subscription-enrollment-flyout');
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
