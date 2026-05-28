<?php

namespace App\Livewire\Admin\Subscriptions;

use App\Jobs\SendSubscriptionNotification;
use App\Models\Member;
use App\Models\Subscription;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
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

    public ?int $transferToMemberId = null;

    public bool $requiresApproval = false;

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

    public function suspend(): void
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

        $subscription->suspend($this->suspensionReason, auth()->id());

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

    public function resume(): void
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

        $subscription->resume(auth()->id());

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

    public function transfer(): void
    {
        $this->authorize('transfer', Subscription::class);

        $subscription = $this->selectedSubscription;

        if ($subscription === null) {
            $this->addError('subscriptionId', 'Select a subscription first.');

            return;
        }

        if (! in_array($subscription->status, ['active', 'suspended'], true)) {
            $this->addError('subscriptionId', 'Only active or suspended subscriptions can be transferred.');

            return;
        }

        $this->validate($this->transferRules($subscription->member_id));

        if (Subscription::query()->where('member_id', (int) $this->transferToMemberId)->active()->exists()) {
            $this->addError('transferToMemberId', 'The selected member already has an active subscription.');

            return;
        }

        $oldMemberId = $subscription->member_id;
        $newSubscription = $subscription->transfer((int) $this->transferToMemberId, auth()->id());

        SendSubscriptionNotification::dispatch(
            $subscription->id,
            'transferred-from',
            $oldMemberId,
            [
                'push_intent' => true,
                'push_status' => 'pending-infrastructure',
                'new_member_id' => $newSubscription->member_id,
                'new_subscription_id' => $newSubscription->id,
            ],
        );
        SendSubscriptionNotification::dispatch(
            $newSubscription->id,
            'transferred-to',
            $newSubscription->member_id,
            [
                'push_intent' => true,
                'push_status' => 'pending-infrastructure',
                'from_member_id' => $oldMemberId,
                'source_subscription_id' => $subscription->id,
            ],
        );

        $this->subscriptionId = $newSubscription->id;
        session(['subscriptions.selected_subscription_id' => $newSubscription->id]);

        $this->action = '';
        $this->transferToMemberId = null;
        $this->requiresApproval = false;

        $this->dispatch('subscription-updated', subscriptionId: $newSubscription->id);
        $this->dispatch('toast', message: 'Subscription transferred successfully', type: 'success');
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

    #[Computed]
    public function availableMembers(): Collection
    {
        if ($this->selectedSubscription === null) {
            return collect();
        }

        return Member::query()
            ->whereNull('deleted_at')
            ->whereKeyNot($this->selectedSubscription->member_id)
            ->whereDoesntHave('activeSubscription')
            ->orderBy('name')
            ->get(['id', 'name']);
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

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function transferRules(int $currentMemberId): array
    {
        return [
            'transferToMemberId' => [
                'required',
                'integer',
                Rule::exists('members', 'id')->whereNull('deleted_at'),
                Rule::notIn([$currentMemberId]),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_numeric($value)) {
                        return;
                    }

                    $hasActiveSubscription = Subscription::query()
                        ->where('member_id', (int) $value)
                        ->active()
                        ->exists();

                    if ($hasActiveSubscription) {
                        $fail('The selected member already has an active subscription.');
                    }
                },
            ],
            'requiresApproval' => ['accepted'],
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.subscriptions.subscription-suspension');
    }
}
