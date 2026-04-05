<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'name',
        'price',
        'duration_days',
        'included_services',
        'is_archived',
    ];

    protected $casts = [
        'price' => 'decimal:3',
        'included_services' => 'array',
        'is_archived' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
