<?php

namespace App\Models;

use Database\Factories\Shared\Activities\ActivityFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    protected static function newFactory(): Factory
    {
        return ActivityFactory::new();
    }

    protected $fillable = [
        'title',
        'category',
        'base_price',
        'currency',
        'image_url',
        'icon',
        'description',
        'features',
        'rating',
        'review_count',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'base_price' => 'decimal:2',
        'rating' => 'decimal:1',
        'is_active' => 'boolean',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(ActivitySlot::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** @use HasFactory<ActivityFactory> */
    use HasFactory;
}
