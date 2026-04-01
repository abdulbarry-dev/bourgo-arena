<?php

namespace App\Livewire\Admin\Subscriptions;

use App\Jobs\SendSubscriptionNotification;
use App\Jobs\SendSubscriptionReceiptEmail;
use App\Jobs\SyncTerminalWhitelist;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\ReceiptGenerator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SubscriptionEnrollment extends Component
{
    use AuthorizesRequests;

    public ?int $memberId = null;

    public ?int $planId = null;

    public string $startsAt = '';

    public string $paymentMethod = 'cash';

    public ?string $paymentReference = null;

    public bool $isProcessing = false;

    public function mount(?int $memberId = null): void
    {
        $this->memberId = $memberId ?? session('members.selected_member_id');
        $this->startsAt = now()->toDateString();
    }

    #[On('member-selected')]
    public function setMember(int $memberId): void
    {
        $this->memberId = $memberId;
        session(['members.selected_member_id' => $memberId]);
    }

    public function updatedPaymentMethod(): void
    {
        if ($this->paymentMethod === 'cash') {
            $this->paymentReference = null;
        }
    }

    public function enroll(): void
    {
        $this->authorize('create', Subscription::class);

        $this->isProcessing = true;

        try {
            $validated = $this->validate($this->rules(), $this->messages());

            if (! config("payment.methods.{$validated['paymentMethod']}", false)) {
                $this->addError('paymentMethod', __('The selected payment method is not enabled.'));

                return;
            }

            $member = Member::query()
                ->whereKey($validated['memberId'])
                ->whereNull('deleted_at')
                ->first();

            if ($member === null) {
                $this->addError('memberId', __('The selected member was not found.'));

                return;
            }

            $plan = Plan::query()
                ->where('is_archived', false)
                ->find($validated['planId']);

            if ($plan === null) {
                $this->addError('planId', __('The selected plan is not available.'));

                return;
            }

            if (Subscription::query()->where('member_id', $member->id)->active()->exists()) {
                $this->addError('memberId', __('This member already has an active subscription.'));

                return;
            }

            $subscription = DB::transaction(function () use ($validated, $member, $plan): Subscription {
                $subscription = Subscription::query()->create([
                    'member_id' => $member->id,
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'starts_at' => $validated['startsAt'],
                    'ends_at' => Subscription::calculateEndDate($validated['startsAt'], (int) $plan->duration_days),
                    'suspended_at' => null,
                    'days_remaining' => null,
                    'resumed_at' => null,
                    'payment_method' => $validated['paymentMethod'],
                    'payment_reference' => $validated['paymentReference'],
                    'amount_paid' => $plan->price,
                    'receipt_path' => null,
                    'enrolled_by' => auth()->id(),
                ]);

                $receiptPath = app(ReceiptGenerator::class)->generate([
                    'member_name' => $member->name,
                    'plan_name' => $plan->name,
                    'amount_paid' => (float) $plan->price,
                    'payment_method' => $validated['paymentMethod'],
                    'payment_reference' => $validated['paymentReference'],
                    'paid_at' => now()->toDateTimeString(),
                    'enrolled_by' => auth()->user()?->name ?? 'System',
                    'subscription_id' => $subscription->id,
                ]);

                $subscription->update(['receipt_path' => $receiptPath]);

                if ($member->status === 'pending') {
                    $member->update(['status' => 'active']);
                }

                return $subscription->fresh();
            });

            SendSubscriptionReceiptEmail::dispatch($subscription->id);
            SendSubscriptionNotification::dispatch(
                $subscription->id,
                'enrolled',
                null,
                [
                    'push_intent' => true,
                    'push_status' => 'pending-infrastructure',
                    'source' => 'subscription_enrollment',
                ],
            );
            SyncTerminalWhitelist::dispatch(
                $member->id,
                $subscription->id,
                ['trigger' => 'subscription_enrolled'],
            );

            $this->dispatch('subscription-created', memberId: $member->id, subscriptionId: $subscription->id);
            $this->dispatch('toast', message: 'Subscription enrolled successfully', type: 'success');
        } finally {
            $this->isProcessing = false;
        }
    }

    #[Computed]
    public function selectedMember(): ?Member
    {
        if ($this->memberId === null) {
            return null;
        }

        return Member::query()
            ->with('activeSubscription.plan')
            ->find($this->memberId);
    }

    #[Computed]
    public function plans(): Collection
    {
        return Plan::query()
            ->where('is_archived', false)
            ->orderBy('price')
            ->get(['id', 'name', 'price', 'duration_days']);
    }

    #[Computed]
    public function selectedPlan(): ?Plan
    {
        if ($this->planId === null) {
            return null;
        }

        return $this->plans->firstWhere('id', $this->planId);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'memberId' => [
                'required',
                'integer',
                Rule::exists('members', 'id')->whereNull('deleted_at'),
            ],
            'planId' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where(fn ($query) => $query->where('is_archived', 0)),
            ],
            'startsAt' => [
                'required',
                'date_format:Y-m-d',
            ],
            'paymentMethod' => [
                'required',
                Rule::in(['cash', 'konnect', 'paymee']),
            ],
            'paymentReference' => [
                Rule::requiredIf($this->paymentMethod !== 'cash'),
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'memberId.required' => __('Select a member before enrollment.'),
            'planId.required' => __('Select a subscription plan.'),
            'startsAt.required' => __('Select a start date.'),
            'paymentReference.required' => __('Payment reference is required for online gateway methods.'),
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.subscriptions.subscription-enrollment');
    }
}
