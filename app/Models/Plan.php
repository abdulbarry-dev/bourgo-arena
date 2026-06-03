<?php

namespace App\Models;

use App\Models\Scopes\ActivePlanScope;
use Database\Factories\Shared\Billing\PlanFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected static function newFactory(): Factory
    {
        return PlanFactory::new();
    }

    use HasFactory;

    protected $fillable = [
        'service_id',
        'name',
        'has_all_courses',
        'price',
        'duration_days',
        'is_archived',
    ];

    protected $casts = [
        'price' => 'decimal:3',
        'has_all_courses' => 'boolean',
        'is_archived' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope(new ActivePlanScope);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
