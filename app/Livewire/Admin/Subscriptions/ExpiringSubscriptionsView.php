<?php

namespace App\Livewire\Admin\Subscriptions;

use App\Jobs\SendSubscriptionNotification;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ExpiringSubscriptionsView extends Component
{
    use AuthorizesRequests;

    /**
     * @var Collection<int, Subscription>
     */
    public Collection $expiringSubscriptions;

    public int $touchedCount = 0;

    public function mount(): void
    {
        $this->authorize('viewAny', Subscription::class);

        $this->loadExpiringSubscriptions();
    }

    #[On('subscription-updated')]
    public function loadExpiringSubscriptions(): void
    {
        $this->authorize('viewAny', Subscription::class);

        $this->expiringSubscriptions = Subscription::query()
            ->expiring()
            ->with(['member', 'plan'])
            ->orderBy('ends_at')
            ->get();
    }

    public function sendReminder(int $subscriptionId): void
    {
        $this->authorize('viewAny', Subscription::class);

        $subscription = Subscription::query()
            ->expiring()
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

        $subscriptions = Subscription::query()
            ->expiring()
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

    public function render(): View
    {
        return view('livewire.admin.subscriptions.expiring-subscriptions-view');
    }
}
