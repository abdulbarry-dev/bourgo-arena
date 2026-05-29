<?php

namespace App\Models;

use Database\Factories\Shared\Billing\PlanFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected static function newFactory(): Factory
    {
        return PlanFactory::new();
    }

    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'has_all_courses',
        'price',
        'duration_days',
        'included_services',
        'is_archived',
        'image_url',
    ];

    protected $casts = [
        'price' => 'decimal:3',
        'has_all_courses' => 'boolean',
        'included_services' => 'array',
        'is_archived' => 'boolean',
    ];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
