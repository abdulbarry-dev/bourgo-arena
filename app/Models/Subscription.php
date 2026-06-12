<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\Shared\Billing\SubscriptionFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected static function newFactory(): Factory
    {
        return SubscriptionFactory::new();
    }

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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'subscription_id');
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

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->daysRemaining() > 0;
    }
}
