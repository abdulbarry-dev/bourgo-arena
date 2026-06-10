<?php

namespace App\Livewire\Admin\Subscriptions;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithPagination;

class ExpiringSubscriptionsView extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public Collection $plans;

    public int $perPage = 10;

    #[Session]
    public string $search = '';

    #[Session]
    public string $planId = '';

    #[Session]
    public string $daysWindow = '7';

    /**
     * @var array<string, string>
     */
    public array $expiryWindows = [
        '3' => '3 days',
        '7' => '7 days',
        '14' => '14 days',
        '30' => '30 days',
    ];

    public function mount(): void
    {
        $this->authorize('viewAny', Subscription::class);

        $this->plans = Plan::query()->orderBy('name')->get();
    }

    #[Computed]
    public function expiringSubscriptions(): LengthAwarePaginator
    {
        $this->authorize('viewAny', Subscription::class);

        return $this->filteredQuery()
            ->with(['member', 'plan'])
            ->orderBy('ends_at')
            ->paginate($this->perPage);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPlanId(): void
    {
        $this->resetPage();
    }

    public function updatedDaysWindow(): void
    {
        $this->resetPage();
    }

    private function filteredQuery(): Builder
    {
        $query = Subscription::query()->active();

        $query->whereDate('ends_at', '<=', now()->addDays($this->daysWindow === 'all' ? 30 : max(1, (int) $this->daysWindow)));

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';

            $query->where(function (Builder $builder) use ($term): void {
                $builder->whereHas('member', function (Builder $memberQuery) use ($term): void {
                    $memberQuery->where('name', 'like', $term)->orWhere('email', 'like', $term);
                })->orWhereHas('plan', function (Builder $planQuery) use ($term): void {
                    $planQuery->where('name', 'like', $term);
                });
            });
        }

        if ($this->planId !== '') {
            $query->where('plan_id', (int) $this->planId);
        }

        return $query;
    }

    public function render(): View
    {
        return view('livewire.admin.subscriptions.expiring-subscriptions-view');
    }
}
