<?php

namespace App\Models;

use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function suspend(): void
    {
        $this->update([
            'status' => 'suspended',
            'days_remaining' => max(0, now()->startOfDay()->diffInDays($this->ends_at, false)),
            'suspended_at' => now(),
        ]);
    }

    public function resume(): void
    {
        $remaining = $this->days_remaining ?? 0;

        $this->update([
            'status' => 'active',
            'ends_at' => now()->addDays($remaining)->toDateString(),
            'resumed_at' => now(),
            'suspended_at' => null,
            'days_remaining' => null,
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at !== null && $this->ends_at->isFuture();
    }
}
