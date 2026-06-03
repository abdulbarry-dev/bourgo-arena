<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityTimeSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'start_time',
        'end_time',
        'max_capacity',
        'reserved_count',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
        ];
    }

    /**
     * Check whether a given time range overlaps any existing time slot for the activity.
     */
    public static function overlaps(int $activityId, string $startsAt, string $endsAt, ?int $ignoreId = null): bool
    {
        $query = self::query()
            ->where('activity_id', $activityId)
            ->where('start_time', '<', $endsAt)
            ->where('end_time', '>', $startsAt);

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
        return $this->hasMany(Reservation::class, 'activity_time_slot_id');
    }

    public function isFullyBooked(): bool
    {
        return $this->reserved_count >= $this->max_capacity;
    }
}
