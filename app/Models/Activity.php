<?php

namespace App\Models;

use Database\Factories\Shared\Activities\ActivityFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    protected static function newFactory(): Factory
    {
        return ActivityFactory::new();
    }

    protected $fillable = [
        'service_id',
        'title',
        'base_price',
        'capacity',
        'image_url',
        'images',
        'description',
        'features',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'images' => 'array',
        'base_price' => 'decimal:2',
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ActivitySession::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(ActivitySlot::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getCurrencyAttribute(): string
    {
        return 'TND';
    }

    /** @use HasFactory<ActivityFactory> */
    use HasFactory;
}
