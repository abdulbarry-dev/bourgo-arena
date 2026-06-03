<?php

namespace App\Livewire\Admin\Subscriptions;

use App\Actions\Subscriptions\ResumeSubscriptionAction;
use App\Actions\Subscriptions\SuspendSubscriptionAction;
use App\Jobs\SendSubscriptionNotification;
use App\Models\Subscription;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SubscriptionSuspension extends Component
{
    use AuthorizesRequests;

    public ?int $subscriptionId = null;

    public string $action = '';

    public string $suspensionReason = '';

    public bool $confirmSuspension = false;

    public function mount(?int $subscriptionId = null): void
    {
        $this->subscriptionId = $subscriptionId ?? session('subscriptions.selected_subscription_id');
    }

    #[On('subscription-selected')]
    public function setSubscription(int $subscriptionId): void
    {
        $this->subscriptionId = $subscriptionId;
        session(['subscriptions.selected_subscription_id' => $subscriptionId]);
    }

    #[On('member-selected')]
    public function setSubscriptionFromMember(int $memberId): void
    {
        $subscription = Subscription::query()
            ->where('member_id', $memberId)
            ->whereIn('status', ['active', 'suspended'])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('ends_at')
            ->first();

        if ($subscription === null) {
            $this->subscriptionId = null;
            session()->forget('subscriptions.selected_subscription_id');

            return;
        }

        $this->setSubscription($subscription->id);
    }

    public function suspend(SuspendSubscriptionAction $suspendAction): void
    {
        $this->authorize('suspend', Subscription::class);

        $subscription = $this->selectedSubscription;

        if ($subscription === null) {
            $this->addError('subscriptionId', 'Select a subscription first.');

            return;
        }

        $this->validate($this->suspendRules());

        if ($subscription->status !== 'active') {
            $this->addError('subscriptionId', 'Only active subscriptions can be suspended.');

            return;
        }

        $suspendAction->execute($subscription, $this->suspensionReason, auth()->id());

        SendSubscriptionNotification::dispatch(
            $subscription->id,
            'suspended',
            null,
            [
                'push_intent' => true,
                'push_status' => 'pending-infrastructure',
                'reason' => $this->suspensionReason,
            ],
        );

        $this->suspensionReason = '';
        $this->confirmSuspension = false;
        $this->action = '';

        $this->dispatch('subscription-updated', subscriptionId: $subscription->id);
        $this->dispatch('toast', message: 'Subscription suspended successfully', type: 'success');
    }

    public function resume(ResumeSubscriptionAction $resumeAction): void
    {
        $this->authorize('resume', Subscription::class);

        $subscription = $this->selectedSubscription;

        if ($subscription === null) {
            $this->addError('subscriptionId', 'Select a subscription first.');

            return;
        }

        if ($subscription->status !== 'suspended') {
            $this->addError('subscriptionId', 'Only suspended subscriptions can be resumed.');

            return;
        }

        $resumeAction->execute($subscription, auth()->id());

        SendSubscriptionNotification::dispatch(
            $subscription->id,
            'resumed',
            null,
            [
                'push_intent' => true,
                'push_status' => 'pending-infrastructure',
            ],
        );

        $this->action = '';

        $this->dispatch('subscription-updated', subscriptionId: $subscription->id);
        $this->dispatch('toast', message: 'Subscription resumed successfully', type: 'success');
    }

    #[Computed]
    public function selectedSubscription(): ?Subscription
    {
        if ($this->subscriptionId === null) {
            return null;
        }

        return Subscription::query()
            ->with([
                'member',
                'plan',
                'auditLogs' => function ($query): void {
                    $query->with('performedBy')->limit(8);
                },
            ])
            ->find($this->subscriptionId);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function suspendRules(): array
    {
        return [
            'suspensionReason' => [
                'required',
                Rule::in(['medical', 'travel', 'other']),
            ],
            'confirmSuspension' => ['accepted'],
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.subscriptions.subscription-suspension');
    }
}
