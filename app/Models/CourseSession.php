<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseSession extends Model
{
    protected $fillable = [
        'course_id',
        'day_of_week',
        'starts_at',
        'duration_minutes',
        'capacity',
        'is_cancelled',
        'cancelled_at',
    ];

    protected $casts = [
        'is_cancelled' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
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
