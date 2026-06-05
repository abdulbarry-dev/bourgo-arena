<?php

namespace App\Services;

use App\Models\ApiReservation;
use App\Models\LoyaltyAuditLog;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\Subscription;
use App\Notifications\LoyaltyPointsUpdatedNotification;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoyaltyCalculatorService
{
    public function __construct(
        protected TierResolutionService $tierResolutionService
    ) {}

    public function creditFixedMonthlyRenewal(Subscription $subscription, ?string $idempotencyKey = null): bool
    {
        $member = $subscription->member()->first();

        if ($member === null) {
            return false;
        }

        $tier = $this->tierResolutionService->resolveTier($member);
        $basePoints = (int) config('loyalty.fixed_monthly_renewal_points', 0);
        $points = (int) round($basePoints * (float) $tier->currentTier->multiplier);

        if ($points <= 0) {
            return false;
        }

        $idempotencyKey ??= 'subscription:'.$subscription->id.':monthly-renewal';

        return $this->creditPoints(
            member: $member,
            points: $points,
            transactionType: 'fixed',
            sourceType: Subscription::class,
            sourceId: $subscription->id,
            idempotencyKey: $idempotencyKey,
            auditAction: 'credit',
            auditMetadata: [
                'tier_label' => $tier->currentTier->label,
                'tier_multiplier' => $tier->currentTier->multiplier,
            ],
        );
    }

    public function creditVariableForReservation(ApiReservation $reservation, ?string $idempotencyKey = null): bool
    {
        $member = $reservation->member()->first();
        $activity = $reservation->activity()->first();

        if ($member === null || $activity === null) {
            return false;
        }

        if ($reservation->payment_status !== 'paid' || $reservation->status === 'cancelled') {
            return false;
        }

        $eligibleCategories = (array) config('loyalty.variable.eligible_categories', []);
        if (! in_array($activity->category, $eligibleCategories, true)) {
            return false;
        }

        $tier = $this->tierResolutionService->resolveTier($member);
        $monthlyCount = ApiReservation::query()
            ->where('member_id', $member->id)
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();

        // Ensure the current reservation counts as the first paid reservation
        // so first reservation in month gives base points.
        $monthlyCount = max(1, $monthlyCount);

        $basePoints = (int) config('loyalty.variable.base_points_per_reservation', 0);
        $points = (int) round($basePoints * $monthlyCount * (float) $tier->currentTier->multiplier);

        // computed points logged previously during debugging; removed for cleanup

        if ($points <= 0) {
            return false;
        }

        $idempotencyKey ??= 'reservation:'.$reservation->id.':variable';

        return $this->creditPoints(
            member: $member,
            points: $points,
            transactionType: 'variable',
            sourceType: ApiReservation::class,
            sourceId: $reservation->id,
            idempotencyKey: $idempotencyKey,
            auditAction: 'credit',
            auditMetadata: [
                'tier_label' => $tier->currentTier->label,
                'tier_multiplier' => $tier->currentTier->multiplier,
                'monthly_paid_reservations' => $monthlyCount,
                'activity_category' => $activity->category,
            ],
        );
    }

    public function giftPoints(Member $member, int $points, string $reason): bool
    {
        if ($points <= 0) {
            return false;
        }

        $success = $this->creditPoints(
            member: $member,
            points: $points,
            transactionType: 'gift',
            sourceType: 'Bourgo Arena',
            sourceId: auth()->id(),
            idempotencyKey: 'gift:'.Str::uuid(),
            auditAction: 'gift',
            auditMetadata: [
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()?->name,
                'reason' => $reason,
            ],
        );

        if ($success) {
            $member->notify(new LoyaltyPointsUpdatedNotification($member, $points, 'gift', $reason));
        }

        return $success;
    }

    public function refundPoints(Member $member, int $points, string $reason): bool
    {
        if ($points <= 0) {
            return false;
        }

        return (bool) DB::transaction(function () use ($member, $points, $reason) {
            $lockedMember = Member::query()->lockForUpdate()->findOrFail($member->id);
            $balanceBefore = (int) ($lockedMember->loyalty_points ?? 0);

            // Ensure we don't go below zero
            $actualPointsToRefund = min($points, $balanceBefore);

            if ($actualPointsToRefund <= 0) {
                return false;
            }

            LoyaltyPoint::query()->create([
                'member_id' => $lockedMember->id,
                'points' => -$actualPointsToRefund,
                'transaction_type' => 'refund',
                'source_type' => 'Bourgo Arena',
                'source_id' => auth()->id(),
                'idempotency_key' => 'refund:'.Str::uuid(),
                'created_at' => now(),
            ]);

            $balanceAfter = $balanceBefore - $actualPointsToRefund;

            $lockedMember->update([
                'loyalty_points' => $balanceAfter,
            ]);

            LoyaltyAuditLog::query()->create([
                'member_id' => $lockedMember->id,
                'action' => 'refund',
                'points_changed' => -$actualPointsToRefund,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source_type' => 'Bourgo Arena',
                'source_id' => auth()->id(),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'metadata' => [
                    'admin_id' => auth()->id(),
                    'admin_name' => auth()->user()?->name,
                    'reason' => $reason,
                    'requested_refund' => $points,
                ],
                'created_at' => now(),
            ]);

            $lockedMember->notify(new LoyaltyPointsUpdatedNotification($lockedMember, $actualPointsToRefund, 'refund', $reason));

            return true;
        });
    }

    /**
     * @param  array<string, mixed>|null  $auditMetadata
     */
    protected function creditPoints(
        Member $member,
        int $points,
        string $transactionType,
        ?string $sourceType,
        ?int $sourceId,
        ?string $idempotencyKey,
        string $auditAction,
        ?array $auditMetadata = null
    ): bool {
        return (bool) DB::transaction(function () use (
            $member,
            $points,
            $transactionType,
            $sourceType,
            $sourceId,
            $idempotencyKey,
            $auditAction,
            $auditMetadata
        ) {
            $lockedMember = Member::query()->lockForUpdate()->findOrFail($member->id);
            $balanceBefore = (int) ($lockedMember->loyalty_points ?? 0);

            try {
                LoyaltyPoint::query()->create([
                    'member_id' => $lockedMember->id,
                    'points' => $points,
                    'transaction_type' => $transactionType,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'idempotency_key' => $idempotencyKey,
                    'created_at' => now(),
                ]);
            } catch (QueryException $exception) {
                if ($this->isUniqueViolation($exception)) {
                    return false;
                }

                throw $exception;
            }

            $balanceAfter = $balanceBefore + $points;

            $lockedMember->update([
                'loyalty_points' => $balanceAfter,
            ]);

            LoyaltyAuditLog::query()->create([
                'member_id' => $lockedMember->id,
                'action' => $auditAction,
                'points_changed' => $points,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'metadata' => $auditMetadata,
                'created_at' => now(),
            ]);

            return true;
        });
    }

    protected function isUniqueViolation(QueryException $exception): bool
    {
        if ($exception instanceof UniqueConstraintViolationException) {
            return true;
        }

        if ($exception->getCode() === '23505') {
            return true;
        }

        if ($exception->getCode() === '23000') {
            return str_contains(strtolower($exception->getMessage()), 'unique');
        }

        $previous = $exception->getPrevious();
        if ($previous !== null && (string) $previous->getCode() === '23505') {
            return true;
        }

        return false;
    }
}
