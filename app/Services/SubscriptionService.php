<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Check if a member can enroll in a specific plan.
     * Implements "Smart Stacking" logic to prevent redundant purchases.
     *
     * @return bool|string True if can enroll, or a string message explaining why not.
     */
    public function validateEnrollment(Member $member, Plan $plan): bool|string
    {
        if ($plan->is_child_only && $member->parent_id === null) {
            return __('This plan is for children only and must be purchased through the family account.');
        }

        Subscription::query()
            ->where('status', 'pending')
            ->where('member_id', $member->id)
            ->where('plan_id', $plan->id)
            ->get()
            ->each(fn (Subscription $pending) => $this->cancelPending($pending));
        $activeSubs = $member->validSubscriptions()
            ->with(['plan.courses'])
            ->whereHas('plan', fn (Builder $query) => $query->where('service_id', $plan->service_id))
            ->get();

        foreach ($activeSubs as $activeSub) {
            $existingPlan = $activeSub->plan;

            // Rule: Exact same plan -> Allow (it will be a renewal/extension)
            if ($existingPlan->id === $plan->id) {
                continue;
            }

            // Rule: If existing is Level 3 (Full Access), any new Level 1 or 2 is redundant
            if ($existingPlan->level === 3 && $plan->level < 3) {
                return __('Your current plan already provides full access to this service. This new plan would be redundant.');
            }

            // Rule: If new is Level 3 (Full Access), existing Level 1 or 2 will be upgraded (Allowed)
            if ($plan->level === 3 && $existingPlan->level < 3) {
                continue;
            }

            // Rule: Same level (e.g. both Level 2)
            if ($plan->level === $existingPlan->level) {
                // If they cover the exact same courses, it's redundant
                $existingCourseIds = $activeSub->plan->courses->pluck('id')->toArray();
                $newCourseIds = $plan->courses()->pluck('courses.id')->toArray();
                sort($existingCourseIds);
                sort($newCourseIds);

                if ($existingCourseIds === $newCourseIds && ! empty($newCourseIds)) {
                    return __('This plan provides no new access beyond your current active subscription.');
                }
            }
        }

        return true;
    }

    /**
     * Enroll a member in a plan (handles Renewal, Upgrade, or Stacking).
     */
    public function enroll(Member $member, Plan $plan, array $data): Subscription
    {
        return DB::transaction(function () use ($member, $plan, $data) {
            $status = $data['status'] ?? 'active';

            if ($status === 'active') {
                // 1. Check for exact plan renewal (Extension)
                $existingSamePlan = Subscription::query()
                    ->active()
                    ->where('member_id', $member->id)
                    ->where('plan_id', $plan->id)
                    ->first();

                if ($existingSamePlan) {
                    $newEndDate = CarbonImmutable::parse($existingSamePlan->ends_at)
                        ->addDays((int) $plan->duration_days)
                        ->toDateString();

                    $existingSamePlan->update([
                        'ends_at' => $newEndDate,
                        'days_remaining' => (int) CarbonImmutable::now()->diffInDays($newEndDate, false),
                        'amount_paid' => $plan->price,
                    ]);

                    return $existingSamePlan->fresh();
                }

                // 2. Check for Tiered Upgrade (Merging)
                $existingLowerTier = Subscription::query()
                    ->active()
                    ->where('member_id', $member->id)
                    ->whereHas('plan', fn (Builder $query) => $query->where('service_id', $plan->service_id)->where('level', '<', $plan->level))
                    ->first();

                if ($existingLowerTier) {
                    $newEndDate = CarbonImmutable::parse($existingLowerTier->ends_at)
                        ->addDays((int) $plan->duration_days)
                        ->toDateString();

                    $existingLowerTier->update([
                        'plan_id' => $plan->id,
                        'ends_at' => $newEndDate,
                        'days_remaining' => (int) CarbonImmutable::now()->diffInDays($newEndDate, false),
                        'amount_paid' => $plan->price,
                    ]);

                    return $existingLowerTier->fresh();
                }
            }

            // 3. Normal Enrollment (or Stacking)
            $subscription = Subscription::query()->create([
                'member_id' => $member->id,
                'plan_id' => $plan->id,
                'status' => $status,
                'starts_at' => $data['starts_at'] ?? now()->toDateString(),
                'ends_at' => Subscription::calculateEndDate($data['starts_at'] ?? now()->toDateString(), (int) $plan->duration_days),
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'amount_paid' => $plan->price,
                'enrolled_by' => $data['enrolled_by'] ?? null,
            ]);

            if ($subscription->status === 'active') {
                $this->finalizeSubscription($subscription, $member, $plan, $data);
            }

            return $subscription;
        });
    }

    /**
     * Activate a pending subscription (handles post-payment Renewal, Upgrade, or Stacking).
     */
    public function activate(Subscription $subscription): Subscription
    {
        if ($subscription->status !== 'pending') {
            return $subscription;
        }

        return DB::transaction(function () use ($subscription) {
            $member = $subscription->member;
            $plan = $subscription->plan;

            // 1. Check for Renewal
            $existingSamePlan = Subscription::query()
                ->active()
                ->where('id', '!=', $subscription->id)
                ->where('member_id', $member->id)
                ->where('plan_id', $plan->id)
                ->first();

            if ($existingSamePlan) {
                $newEndDate = CarbonImmutable::parse($existingSamePlan->ends_at)
                    ->addDays((int) $plan->duration_days)
                    ->toDateString();

                $existingSamePlan->update([
                    'ends_at' => $newEndDate,
                    'days_remaining' => (int) CarbonImmutable::now()->diffInDays($newEndDate, false),
                    'payment_method' => $subscription->payment_method,
                    'payment_reference' => $subscription->payment_reference,
                    'amount_paid' => $subscription->amount_paid,
                ]);

                $this->finalizeSubscription($existingSamePlan, $member, $plan, [
                    'payment_method' => $subscription->payment_method,
                    'payment_reference' => $subscription->payment_reference,
                ]);

                $subscription->delete();

                return $existingSamePlan->fresh();
            }

            // 2. Check for Upgrade
            $existingLowerTier = Subscription::query()
                ->active()
                ->where('id', '!=', $subscription->id)
                ->where('member_id', $member->id)
                ->whereHas('plan', fn (Builder $query) => $query->where('service_id', $plan->service_id)->where('level', '<', $plan->level))
                ->first();

            if ($existingLowerTier) {
                $newEndDate = CarbonImmutable::parse($existingLowerTier->ends_at)
                    ->addDays((int) $plan->duration_days)
                    ->toDateString();

                $existingLowerTier->update([
                    'plan_id' => $plan->id,
                    'ends_at' => $newEndDate,
                    'days_remaining' => (int) CarbonImmutable::now()->diffInDays($newEndDate, false),
                    'payment_method' => $subscription->payment_method,
                    'payment_reference' => $subscription->payment_reference,
                    'amount_paid' => $subscription->amount_paid,
                ]);

                $this->finalizeSubscription($existingLowerTier, $member, $plan, [
                    'payment_method' => $subscription->payment_method,
                    'payment_reference' => $subscription->payment_reference,
                ]);

                $subscription->delete();

                return $existingLowerTier->fresh();
            }

            // 3. Standard Activation
            $subscription->update([
                'status' => 'active',
                'starts_at' => now()->toDateString(),
                'ends_at' => Subscription::calculateEndDate(now(), (int) $plan->duration_days),
            ]);

            $this->finalizeSubscription($subscription, $member, $plan, [
                'payment_method' => $subscription->payment_method,
                'payment_reference' => $subscription->payment_reference,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Finalize an active subscription (update member status, credit loyalty).
     */
    public function finalizeSubscription(Subscription $subscription, Member $member, Plan $plan, array $data): void
    {
        if ($member->status === 'pending') {
            $member->update(['status' => 'active']);
        }

        app(LoyaltyCalculatorService::class)->creditFixedMonthlyRenewal($subscription);
    }

    public function getActiveForUser($member): ?Subscription
    {
        return $this->getActiveSubscriptionsForUser($member)->first();
    }

    /**
     * Get all active subscriptions for the given member.
     *
     * @param  Member  $member
     * @return Collection<Subscription>
     */
    public function getActiveSubscriptionsForUser($member)
    {
        return $member->validSubscriptions()
            ->with(['plan.service'])
            ->orderByDesc('ends_at')
            ->get();
    }

    /**
     * Cancel a pending subscription and mark its active payments as failed.
     */
    public function cancelPending(Subscription $subscription): void
    {
        DB::transaction(function () use ($subscription) {
            $subscription->payments()
                ->whereIn('status', ['pending', 'initiated'])
                ->get()
                ->each(function (Payment $payment): void {
                    $payment->update([
                        'status' => 'failed',
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'cancelled_reason' => 'stale_pending_subscription',
                        ]),
                    ]);
                });

            $subscription->update(['status' => 'cancelled']);
        });
    }

    private function isStalePending(Subscription $subscription): bool
    {
        $timeout = (int) config('payment.subscription.pending_timeout_minutes', 30);

        if ($subscription->created_at->diffInMinutes(now()) < $timeout) {
            return false;
        }

        return ! $subscription->payments()
            ->whereIn('status', ['pending', 'initiated'])
            ->where('updated_at', '>=', now()->subMinutes($timeout))
            ->exists();
    }
}
