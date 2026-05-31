<?php

namespace App\Models;

use Database\Factories\Shared\Activities\ActivitySlotFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivitySlot extends Model
{
    protected static function newFactory(): Factory
    {
        return ActivitySlotFactory::new();
    }

    protected $fillable = [
        'activity_id',
        'starts_at',
        'ends_at',
        'capacity',
        'booked_count',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    /**
     * Check whether a given time range overlaps any existing slot for the activity.
     */
    public static function overlaps(int $activityId, string $startsAt, string $endsAt, ?int $ignoreId = null): bool
    {
        $query = self::query()
            ->where('activity_id', $activityId)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);

        if ($ignoreId !== null) {
            $query->where('id', '<>', $ignoreId);
        }

        return $query->exists();
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(ApiReservation::class);
    }

    public function isFullyBooked(): bool
    {
        return $this->booked_count >= $this->capacity;
    }

    /** @use HasFactory<ActivitySlotFactory> */
    use HasFactory;
}
