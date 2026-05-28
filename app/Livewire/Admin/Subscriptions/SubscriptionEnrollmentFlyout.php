<?php

namespace App\Livewire\Admin\Subscriptions;

use App\Jobs\SendSubscriptionNotification;
use App\Jobs\SendSubscriptionReceiptEmail;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\LoyaltyCalculatorService;
use App\Services\PaymentGateway\KonnectGateway;
use App\Services\ReceiptGenerator;
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
        session(['members.selected_member_id' => $memberId]);
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

            if (Subscription::query()->where('member_id', $member->id)->active()->exists()) {
                $this->addError('memberId', __('This member already has an active subscription.'));

                return;
            }

            // If payment method is a gateway, verify the provided paymentReference server-side
            if ($validated['paymentMethod'] === 'konnect') {
                if (empty($validated['paymentReference'])) {
                    $this->addError('paymentReference', __('Payment reference is required for online gateway methods.'));

                    return;
                }

                $existing = Payment::query()->where('payment_reference', $validated['paymentReference'])->first();

                $verified = false;

                if ($existing && $existing->status === 'paid') {
                    $verified = true;
                } else {
                    try {
                        $verifyResult = app(KonnectGateway::class)->verify($validated['paymentReference']);
                        if (! empty($verifyResult['status']) && in_array(strtolower($verifyResult['status']), ['paid', 'completed'], true)) {
                            $verified = true;
                            $existing = $existing ?? Payment::create([
                                'member_id' => $member->id,
                                'subscription_id' => null,
                                'driver' => 'konnect',
                                'type' => 'subscription_enrollment',
                                'amount' => $plan->price,
                                'currency' => 'TND',
                                'status' => 'paid',
                                'payment_reference' => $validated['paymentReference'],
                                'gateway_transaction_id' => $verifyResult['transaction_id'] ?? null,
                                'metadata' => $verifyResult,
                            ]);
                            $existing->update(['status' => 'paid', 'metadata' => $verifyResult, 'verified_at' => now()]);
                        }
                    } catch (Throwable $e) {
                        $this->addError('paymentReference', __('Payment verification failed.'));

                        return;
                    }
                }

                if (! $verified) {
                    $this->addError('paymentReference', __('Payment has not been verified as paid.'));

                    return;
                }
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
            ->with('activeSubscription.plan')
            ->find($this->memberId);
    }

    #[Computed]
    public function eligibleMembers(): Collection
    {
        return Member::query()
            ->whereNull('deleted_at')
            ->where(function (Builder $query): void {
                $query->whereDoesntHave('activeSubscription');

                if ($this->memberId !== null) {
                    $query->orWhere('id', $this->memberId);
                }
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
            ->get(['id', 'name', 'price', 'duration_days', 'included_services']);
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
                Rule::in(['cash', 'konnect']),
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
        return view('livewire.admin.subscriptions.subscription-enrollment-flyout');
    }
}
