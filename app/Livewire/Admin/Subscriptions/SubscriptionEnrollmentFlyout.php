<?php

namespace App\Livewire\Admin\Subscriptions;

use App\Jobs\SendSubscriptionNotification;
use App\Jobs\SendSubscriptionReceiptEmail;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\PaymentAuditService;
use App\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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
        ]);
        $this->memberId = $memberId;
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

    public function enroll(SubscriptionService $service): void
    {
        $this->authorize('create', Subscription::class);

        $this->isProcessing = true;

        try {
            $validated = $this->validate($this->rules(), $this->messages());

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

            // Use service for validation
            $validationResult = $service->validateEnrollment($member, $plan);
            if ($validationResult !== true) {
                $this->addError('planId', $validationResult);
                $this->isProcessing = false;

                return;
            }

            // Use service for enrollment
            $subscription = $service->enroll($member, $plan, [
                'status' => 'active',
                'starts_at' => $validated['startsAt'],
                'payment_method' => 'cash',
                'payment_reference' => null,
                'enrolled_by' => Auth::id(),
                'enrolled_by_name' => Auth::user()?->name ?? 'System',
            ]);

            $payment = Payment::create([
                'member_id' => $member->id,
                'subscription_id' => $subscription->id,
                'type' => 'subscription',
                'amount' => $plan->price,
                'status' => 'paid',
                'driver' => 'manual',
                'gateway' => 'manual_admin',
                'payment_reference' => 'cash_'.now()->timestamp,
            ]);

            app(PaymentAuditService::class)->log($payment, [
                'payment_gateway' => 'manual_admin',
                'transaction_status' => 'success',
                'transaction_id' => 'manual_'.$payment->id.'_'.time(),
            ]);

            // Check if it was an upgrade by comparing plan_id (service handles the actual update)
            // Or just check if the ID already existed. But for simplicity, we just dispatch appropriate events.

            $this->show = false;

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

            $this->dispatch('subscription-created', memberId: $member->id, subscriptionId: $subscription->id);
            $this->dispatch('toast', message: 'Subscription enrolled successfully', type: 'success');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            $this->addError('enroll', __('Enrollment could not be completed right now. Please try again.'));
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
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.subscriptions.subscription-enrollment-flyout');
    }
}
