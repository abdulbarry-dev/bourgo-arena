<?php

namespace App\Livewire\Admin\Subscriptions;

use App\Jobs\SendSubscriptionNotification;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;

class ExpiringSubscriptionsView extends Component
{
    use AuthorizesRequests;

    /**
     * @var Collection<int, Subscription>
     */
    public Collection $expiringSubscriptions;

    public Collection $plans;

    #[Session]
    public string $search = '';

    #[Session]
    public string $planId = '';

    #[Session]
    public string $daysWindow = '7';

    public int $touchedCount = 0;

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

        $this->loadExpiringSubscriptions();
    }

    #[On('subscription-updated')]
    public function loadExpiringSubscriptions(): void
    {
        $this->authorize('viewAny', Subscription::class);

        $this->expiringSubscriptions = $this->filteredQuery()
            ->with(['member', 'plan'])
            ->orderBy('ends_at')
            ->get();
    }

    public function updatedSearch(): void
    {
        $this->loadExpiringSubscriptions();
    }

    public function updatedPlanId(): void
    {
        $this->loadExpiringSubscriptions();
    }

    public function updatedDaysWindow(): void
    {
        $this->loadExpiringSubscriptions();
    }

    public function sendReminder(int $subscriptionId): void
    {
        $this->authorize('viewAny', Subscription::class);

        $subscription = $this->filteredQuery()
            ->with(['member', 'plan'])
            ->find($subscriptionId);

        if ($subscription === null) {
            $this->addError('subscriptionId', __('Subscription is not eligible for an expiry reminder.'));

            return;
        }

        SendSubscriptionNotification::dispatch(
            $subscription->id,
            'expiry-reminder',
            $subscription->member_id,
            [
                'push_intent' => true,
                'push_status' => 'pending-infrastructure',
                'trigger' => 'expiring_subscription_reminder',
            ],
        );

        $this->touchedCount++;

        $this->dispatch('reminder-sent', subscriptionId: $subscription->id);
        $this->dispatch('toast', message: 'Expiry reminder queued', type: 'success');
    }

    public function sendReminderToAll(): void
    {
        $this->authorize('viewAny', Subscription::class);

        $subscriptions = $this->filteredQuery()
            ->with(['member', 'plan'])
            ->orderBy('ends_at')
            ->get();

        $count = 0;

        foreach ($subscriptions as $subscription) {
            SendSubscriptionNotification::dispatch(
                $subscription->id,
                'expiry-reminder',
                $subscription->member_id,
                [
                    'push_intent' => true,
                    'push_status' => 'pending-infrastructure',
                    'trigger' => 'expiring_subscription_bulk_reminder',
                ],
            );

            $count++;
        }

        $this->expiringSubscriptions = $subscriptions;
        $this->touchedCount += $count;

        if ($count === 0) {
            $this->dispatch('toast', message: 'No expiring subscriptions to remind', type: 'info');

            return;
        }

        $this->dispatch('reminders-sent', count: $count);
        $this->dispatch('toast', message: __('Queued :count reminder notifications', ['count' => $count]), type: 'success');
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
