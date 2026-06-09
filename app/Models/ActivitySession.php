<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivitySession extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'day_of_week',
        'starts_at',
        'starts_at_date',
        'ends_at_date',
        'duration_minutes',
        'is_cancelled',
        'cancelled_at',
    ];

    protected $casts = [
        'starts_at_date' => 'date',
        'ends_at_date' => 'date',
        'is_cancelled' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function getStatus(Carbon $date): string
    {
        if ($this->is_cancelled || $this->exceptions()->where('date', $date->toDateString())->where('is_cancelled', true)->exists()) {
            return 'canceled';
        }

        $endDateTime = $this->getEndDateTime($date);

        if ($endDateTime->isPast()) {
            return 'validated';
        }

        return 'setted';
    }

    public function getEndDateTime(Carbon $date): Carbon
    {
        return Carbon::parse($date->toDateString().' '.$this->starts_at)
            ->addMinutes($this->duration_minutes);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public static function hasOverlap(int $activityId, int $dayOfWeek, string $startTime, int $durationMinutes, ?int $excludeId = null): bool
    {
        $newStart = Carbon::parse($startTime);
        $newEnd = $newStart->copy()->addMinutes($durationMinutes);

        $query = self::where('activity_id', $activityId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_cancelled', false);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existingSessions = $query->get(['id', 'starts_at', 'duration_minutes']);

        foreach ($existingSessions as $session) {
            $existingStart = Carbon::parse($session->starts_at);
            $existingEnd = $existingStart->copy()->addMinutes($session->duration_minutes);

            if ($newStart->lt($existingEnd) && $newEnd->gt($existingStart)) {
                return true;
            }
        }

        return false;
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(ActivitySessionException::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(ApiReservation::class, 'activity_session_id');
    }
}
