<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'day_of_week',
        'starts_at',
        'starts_at_date',
        'ends_at_date',
        'duration_minutes',
        'capacity',
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

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public static function hasOverlap(int $courseId, int $dayOfWeek, string $startTime, int $durationMinutes, ?int $excludeId = null): bool
    {
        $newStart = Carbon::parse($startTime);
        $newEnd = $newStart->copy()->addMinutes($durationMinutes);

        $query = self::where('course_id', $courseId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_cancelled', false);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existingSessions = $query->get(['id', 'starts_at', 'duration_minutes']);

        foreach ($existingSessions as $session) {
            $existingStart = Carbon::parse($session->starts_at);
            $existingEnd = $existingStart->copy()->addMinutes($session->duration_minutes);

            // Overlap condition: (StartA < EndB) AND (EndA > StartB)
            if ($newStart->lt($existingEnd) && $newEnd->gt($existingStart)) {
                return true;
            }
        }

        return false;
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(CourseSessionException::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
