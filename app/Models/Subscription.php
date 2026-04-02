<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SubscriptionFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'member_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'suspended_at',
        'days_remaining',
        'resumed_at',
        'payment_method',
        'payment_reference',
        'amount_paid',
        'receipt_path',
        'enrolled_by',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'suspended_at' => 'datetime',
        'resumed_at' => 'datetime',
        'amount_paid' => 'decimal:3',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(SubscriptionAuditLog::class)->orderByDesc('performed_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->whereDate('ends_at', '>', now());
    }

    public function scopeExpiring(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereDate('ends_at', '<=', now()->addDays(7));
    }

    public static function calculateEndDate(string|DateTimeInterface $startsAt, int $durationDays): string
    {
        return CarbonImmutable::parse($startsAt, config('app.timezone'))
            ->startOfDay()
            ->addDays(max(0, $durationDays))
            ->toDateString();
    }

    public function daysRemaining(): int
    {
        if ($this->ends_at === null) {
            return 0;
        }

        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $endDate = CarbonImmutable::parse($this->ends_at->toDateString(), config('app.timezone'))->startOfDay();

        return max(0, $today->diffInDays($endDate, false));
    }

    public function suspend(?string $reason = null, ?int $performedBy = null): void
    {
        $remainingDays = $this->daysRemaining();

        $this->update([
            'status' => 'suspended',
            'days_remaining' => $remainingDays,
            'suspended_at' => now(),
            'resumed_at' => null,
        ]);

        $this->createAuditLog(
            action: 'suspend',
            reason: $reason,
            fromMemberId: $this->member_id,
            toMemberId: null,
            performedBy: $this->resolvePerformedBy($performedBy),
            metadata: ['remaining_days' => $remainingDays],
        );
    }

    public function resume(?int $performedBy = null): void
    {
        $remaining = max(0, $this->days_remaining ?? $this->daysRemaining());
        $resumedAt = now();

        $this->update([
            'status' => 'active',
            'ends_at' => self::calculateEndDate($resumedAt, $remaining),
            'resumed_at' => $resumedAt,
            'suspended_at' => null,
            'days_remaining' => null,
        ]);

        $this->createAuditLog(
            action: 'resume',
            reason: null,
            fromMemberId: $this->member_id,
            toMemberId: null,
            performedBy: $this->resolvePerformedBy($performedBy),
            metadata: ['restored_days' => $remaining],
        );
    }

    public function transfer(int $newMemberId, ?int $performedBy = null): self
    {
        if ($newMemberId === $this->member_id) {
            throw new InvalidArgumentException('Cannot transfer a subscription to the same member.');
        }

        if (! Member::query()->whereKey($newMemberId)->exists()) {
            throw new InvalidArgumentException('The target member for transfer does not exist.');
        }

        if (self::query()->where('member_id', $newMemberId)->active()->exists()) {
            throw new InvalidArgumentException('The target member already has an active subscription.');
        }

        if (! in_array($this->status, ['active', 'suspended'], true)) {
            throw new InvalidArgumentException('Only active or suspended subscriptions can be transferred.');
        }

        return DB::transaction(function () use ($newMemberId, $performedBy): self {
            $transferDate = now()->startOfDay();
            $remainingDays = $this->status === 'suspended'
                ? max(0, $this->days_remaining ?? 0)
                : $this->daysRemaining();
            $oldMemberId = $this->member_id;
            $actorId = $this->resolvePerformedBy($performedBy) ?? $this->enrolled_by;

            $newSubscription = self::query()->create([
                'member_id' => $newMemberId,
                'plan_id' => $this->plan_id,
                'status' => 'active',
                'starts_at' => $transferDate->toDateString(),
                'ends_at' => self::calculateEndDate($transferDate, $remainingDays),
                'suspended_at' => null,
                'days_remaining' => null,
                'resumed_at' => null,
                'payment_method' => $this->payment_method,
                'payment_reference' => null,
                'amount_paid' => 0,
                'receipt_path' => null,
                'enrolled_by' => $actorId,
            ]);

            $this->update([
                'status' => 'transferred',
                'ends_at' => $transferDate->toDateString(),
                'suspended_at' => null,
                'resumed_at' => null,
                'days_remaining' => $remainingDays,
            ]);

            $this->createAuditLog(
                action: 'transfer',
                reason: null,
                fromMemberId: $oldMemberId,
                toMemberId: $newMemberId,
                performedBy: $actorId,
                metadata: [
                    'remaining_days' => $remainingDays,
                    'new_subscription_id' => $newSubscription->id,
                ],
            );

            return $newSubscription;
        });
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->daysRemaining() > 0;
    }

    private function createAuditLog(
        string $action,
        ?string $reason,
        ?int $fromMemberId,
        ?int $toMemberId,
        ?int $performedBy,
        array $metadata = [],
    ): void {
        $this->auditLogs()->create([
            'action' => $action,
            'reason' => $reason,
            'from_member_id' => $fromMemberId,
            'to_member_id' => $toMemberId,
            'performed_by' => $performedBy,
            'performed_at' => now(),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    private function resolvePerformedBy(?int $performedBy): ?int
    {
        if ($performedBy !== null) {
            return $performedBy;
        }

        return auth()->id();
    }
}
