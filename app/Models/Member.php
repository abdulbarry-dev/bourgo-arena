<?php

namespace App\Models;

use App\UserRole;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Member extends Authenticatable
{
    /** @use HasFactory<MemberFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'email',
        'phone',
        'email_verified_at',
        'phone_verified_at',
        'date_of_birth',
        'gender',
        'emergency_contact',
        'avatar',
        'status',
        'rgpd_consented_at',
        'onboarding_completed_at',
        'password',
        'is_family_account',
        'otp_code',
        'otp_expires_at',
        'otp_attempts',
        'otp_last_sent_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'rgpd_consented_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'otp_last_sent_at' => 'datetime',
        'password' => 'hashed',
        'otp_code' => 'hashed',
        'is_family_account' => 'boolean',
    ];

    protected $hidden = [
        'password',
        'otp_code',
        'remember_token',
    ];

    public function isVerified(): bool
    {
        return $this->email_verified_at !== null || $this->phone_verified_at !== null;
    }

    public function isOnboardingCompleted(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPendingVerification(): bool
    {
        return $this->status === 'pending_verification';
    }

    public function isPendingOnboarding(): bool
    {
        return $this->status === 'pending_onboarding';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Member::class, 'parent_id');
    }

    public function nfcCard(): HasOne
    {
        return $this->hasOne(NfcCard::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->whereDate('ends_at', '>', now());
    }

    public function checkInEvents(): HasMany
    {
        return $this->hasMany(CheckInEvent::class);
    }

    public function onboardingTokens(): HasMany
    {
        return $this->hasMany(MemberOnboardingToken::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(MemberDeviceToken::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(MemberNotification::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(ApiReservation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeByStatus(Builder $query, array|string $status): Builder
    {
        if (is_array($status)) {
            return $query->whereIn('status', $status);
        }

        return $query->where('status', $status);
    }

    public function scopeByPlan(Builder $query, int $planId): Builder
    {
        return $query->whereHas('activeSubscription', function (Builder $query) use ($planId): void {
            $query->where('plan_id', $planId);
        });
    }

    public function scopeWithDetails(Builder $query): Builder
    {
        return $query->with(['activeSubscription', 'nfcCard']);
    }

    public function scopeSearchable(Builder $query, ?string $term): Builder
    {
        if ($term === null || $term === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term): void {
            $builder
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    public function isChild(): bool
    {
        return $this->parent_id !== null;
    }

    public function isParent(): bool
    {
        return $this->children()->exists();
    }

    public function getAccountTypeLabelAttribute(): string
    {
        if ($this->isChild()) {
            return __('Managed Child');
        }

        if ($this->isParent()) {
            return __('Head of Family');
        }

        return __('Individual Member');
    }

    public function getFallbackEmailAttribute(): ?string
    {
        return $this->email ?? $this->parent?->email;
    }

    public function getFallbackPhoneAttribute(): ?string
    {
        return $this->phone ?? $this->parent?->phone;
    }

    public function getRoleAttribute(): UserRole
    {
        return UserRole::Member;
    }
}
