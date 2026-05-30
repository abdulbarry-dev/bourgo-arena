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
        'date',
        'start_time',
        'end_time',
        'max_capacity',
        'reserved_count',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_available' => 'boolean',
        ];
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
