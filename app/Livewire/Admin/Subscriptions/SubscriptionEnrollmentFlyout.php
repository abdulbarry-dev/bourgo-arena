<?php

namespace App\Livewire\Admin\Subscriptions;

use App\Jobs\SendSubscriptionNotification;
use App\Jobs\SendSubscriptionReceiptEmail;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\LoyaltyCalculatorService;
use App\Services\ReceiptGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class SubscriptionEnrollmentFlyout extends Component
{
    public bool $show = false;

    #[On('open-subscription-enrollment-flyout')]
    public function open(?int $memberId = null): void
    {
        $this->authorize('create', Subscription::class);
        $this->resetValidation();
        $this->reset([
            'planId',
            'paymentReference',
        ]);
        $this->memberId = $memberId;
        $this->paymentMethod = 'cash';
        $this->startsAt = now()->toDateString();

        // Pre-select the first eligible member if memberId is null
        if ($this->memberId === null) {
            $firstEligibleMember = $this->eligibleMembers->first();
            if ($firstEligibleMember) {
                $this->memberId = $firstEligibleMember->id;
            }
        }

        // Pre-select the first available plan if planId is null
        if ($this->planId === null) {
            $firstAvailablePlan = $this->plans->first();
            if ($firstAvailablePlan) {
                $this->planId = $firstAvailablePlan->id;
            }
        }

        $this->show = true;
    }

    use AuthorizesRequests;

    public ?int $memberId = null;

    public ?int $planId = null;

    public string $startsAt = '';

    public string $paymentMethod = 'cash';

    public ?string $paymentReference = null;

    public bool $isProcessing = false;

    public function mount(?int $memberId = null): void
    {
        $memberIdFromQuery = request()->integer('member');

        $this->memberId = $memberId
            ?? ($memberIdFromQuery > 0 ? $memberIdFromQuery : session('members.selected_member_id'));

        if ($this->memberId !== null) {
            session(['members.selected_member_id' => $this->memberId]);
        }

        $this->startsAt = now()->toDateString();
    }

    #[On('member-selected')]
    public function setMember(int $memberId): void
    {
        $this->memberId = $memberId;
        session(['members.selected_member_id' => $this->memberId]);
    }

    public function updatedMemberId(mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->memberId = null;
            session()->forget('members.selected_member_id');

            return;
        }

        $this->memberId = (int) $value;
        session(['members.selected_member_id' => $this->memberId]);
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

            // Business Rule 1: Check for existing active subscriptions in the same service
            $existingActiveSubscriptionInSameService = Subscription::query()
                ->active()
                ->where('member_id', $member->id)
                ->whereHas('plan', fn (Builder $query) => $query->where('service_id', $plan->service_id))
                ->first();

            if ($existingActiveSubscriptionInSameService) {
                // If it's an upgrade (new plan has higher duration)
                if ($plan->duration_days > $existingActiveSubscriptionInSameService->plan->duration_days) {
                    // Upgrade mechanism: extend duration
                    $newEndDate = CarbonImmutable::parse($existingActiveSubscriptionInSameService->ends_at)
                        ->addDays($plan->duration_days)
                        ->toDateString();

                    $existingActiveSubscriptionInSameService->update([
                        'plan_id' => $plan->id, // Update to the new plan
                        'ends_at' => $newEndDate,
                        // Recalculate days_remaining based on the new end date
                        'days_remaining' => CarbonImmutable::now()->diffInDays($newEndDate, false),
                    ]);

                    $this->dispatch('subscription-upgraded', memberId: $member->id, subscriptionId: $existingActiveSubscriptionInSameService->id);
                    $this->dispatch('toast', message: __('Subscription upgraded successfully, duration extended.'), type: 'success');
                    $this->isProcessing = false;
                    $this->show = false; // Close the flyout after successful upgrade

                    return;

                } else {
                    // Block enrollment: member cannot hold multiple active subscriptions in same service
                    $this->addError('planId', __('This member already has an active subscription in this service. Please wait for it to expire or upgrade to a higher duration plan.'));
                    $this->isProcessing = false;

                    return;
                }
            }

            // Process enrollment
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
                    'enrolled_by' => Auth::id(),
                ]);

                $receiptPath = app(ReceiptGenerator::class)->generate([
                    'member_name' => $member->name,
                    'plan_name' => $plan->name,
                    'amount_paid' => (float) $plan->price,
                    'payment_method' => $validated['paymentMethod'],
                    'payment_reference' => $validated['paymentReference'],
                    'paid_at' => now()->toDateTimeString(),
                    'enrolled_by' => Auth::user()?->name ?? 'System',
                    'subscription_id' => $subscription->id,
                ]);

                $subscription->update(['receipt_path' => $receiptPath]);

                if ($member->status === 'pending') {
                    $member->update(['status' => 'active']);
                }

                return $subscription->fresh();
            });

            app(LoyaltyCalculatorService::class)->creditFixedMonthlyRenewal($subscription);

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

            $this->show = false; // Close the flyout before dispatching events for better perceived responsiveness

            $this->dispatch('subscription-created', memberId: $member->id, subscriptionId: $subscription->id);
            $this->dispatch('toast', message: 'Subscription enrolled successfully', type: 'success');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            $this->addError('enroll', __('Enrollment could not be completed right now. Please try again.'));
        } finally {
            $this->isProcessing = false;
            // No need to set $this->show = false here anymore, it's done before dispatches for success.
            // If there are errors, we want the flyout to remain open.
        }
    }

    #[Computed]
    public function selectedMember(): ?Member
    {
        if ($this->memberId === null) {
            return null;
        }

        return Member::query()
            ->whereNull('deleted_at')
            ->with('validSubscriptions.plan')
            ->find($this->memberId);
    }

    #[Computed]
    public function eligibleMembers(): Collection
    {
        return Member::query()
            ->whereNull('deleted_at')
            ->when($this->memberId !== null, function (Builder $query) {
                $query->where('id', $this->memberId);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status']);
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
                Rule::in(['cash', 'tpe']),
            ],
            'paymentReference' => [
                Rule::requiredIf($this->paymentMethod === 'tpe'),
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
        return view('livewire.admin.subscriptions.subscription-enrollment-flyout');
    }
}
