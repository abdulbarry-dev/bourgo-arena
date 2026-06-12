<?php

namespace App\Models;

use App\UserRole;
use Database\Factories\Shared\Members\MemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
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
        'state',
        'rgpd_consented_at',
        'onboarding_completed_at',
        'password',
        'is_family_account',
        'is_archived',
        'scheduled_for_deletion_at',
        'otp_code',
        'otp_expires_at',
        'otp_attempts',
        'otp_last_sent_at',
        'loyalty_points',
        'preferences',
        'last_payment_ip',
        'last_payment_country',
        'last_payment_at',
    ];

    protected $casts = [

        'date_of_birth' => 'date',
        'rgpd_consented_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'otp_last_sent_at' => 'datetime',
        'scheduled_for_deletion_at' => 'datetime',
        'password' => 'hashed',
        'otp_code' => 'hashed',
        'is_family_account' => 'boolean',
        'is_archived' => 'boolean',
        'preferences' => 'array',
    ];

    protected static function newFactory(): Factory
    {
        return MemberFactory::new();
    }

    protected $hidden = [
        'password',
        'otp_code',
        'remember_token',
    ];

    /**
     * @return Attribute<string|null, never>
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (blank($this->avatar)) {
                return null;
            }

            if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                return $this->avatar;
            }

            return asset('storage/'.$this->avatar);
        });
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function isVerified(): bool
    {
        return $this->email_verified_at !== null || $this->phone_verified_at !== null;
    }

    public function isFullyVerified(): bool
    {
        return $this->email_verified_at !== null && $this->phone_verified_at !== null;
    }

    public function isPendingAdditionalVerification(): bool
    {
        return $this->state === 'pending_additional_verification';
    }

    public function getVerificationStatus(): array
    {
        return [
            'email_verified' => $this->email_verified_at !== null,
            'phone_verified' => $this->phone_verified_at !== null,
            'onboarding_completed' => $this->isOnboardingCompleted(),
            'is_fully_verified' => $this->isFullyVerified(),
            'email' => $this->email,
            'phone' => $this->phone,
            'unverified_method' => $this->email_verified_at === null ? 'email' : ($this->phone_verified_at === null ? 'phone' : null),
        ];
    }

    public function isOnboardingCompleted(): bool
    {
        $has_onboarded = $this->onboarding_completed_at !== null;

        // Require at least one verified OTP method (email or phone) to
        // consider onboarding complete. This ensures accounts flagged as
        // completed also have a verified contact method.
        $has_verified_contact = $this->email_verified_at !== null || $this->phone_verified_at !== null;

        return $has_onboarded && $has_verified_contact;
    }

    public function isActive(): bool
    {
        return $this->state === 'active';
    }

    public function isPendingVerification(): bool
    {
        return $this->state === 'pending_verification';
    }

    public function isPendingOnboarding(): bool
    {
        return $this->state === 'pending_onboarding';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Member::class, 'parent_id');
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

    public function validSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)
            ->where('status', 'active')
            ->whereDate('ends_at', '>', now());
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

    public function loyaltyPoints(): HasMany
    {
        return $this->hasMany(LoyaltyPoint::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(ApiReservation::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function hasAccessToCourse(Course $course): bool
    {
        return $this->validSubscriptions()
            ->with('plan.courses')
            ->get()
            ->contains(function (Subscription $subscription) use ($course): bool {
                $plan = $subscription->plan;

                if ($plan === null) {
                    return false;
                }

                if ($plan->has_all_courses) {
                    return true;
                }

                return $plan->courses->contains('id', $course->id);
            });
    }

    public function accessibleCourseIds(): ?array
    {
        $subscriptions = $this->validSubscriptions()
            ->with('plan.courses')
            ->get();

        if ($subscriptions->contains(fn (Subscription $sub): bool => $sub->plan?->has_all_courses)) {
            return null;
        }

        return $subscriptions->flatMap(fn (Subscription $sub) => $sub->plan?->courses->pluck('id') ?? collect()
        )->unique()->values()->toArray();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeByState(Builder $query, array|string $state): Builder
    {
        if (is_array($state)) {
            return $query->whereIn('state', $state);
        }

        return $query->where('state', $state);
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
        return $query->whereHas('validSubscriptions', function (Builder $query) use ($planId): void {
            $query->where('plan_id', $planId);
        });
    }

    public function scopeWithDetails(Builder $query): Builder
    {
        return $query->with(['validSubscriptions']);
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
