<?php

namespace App\Models;

use Database\Factories\Dashboard\Events\EventFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected static function newFactory(): Factory
    {
        return EventFactory::new();
    }

    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $fillable = [
        'service_id', 'name', 'description', 'format', 'max_participants',
        'registration_deadline', 'start_date', 'end_date', 'requires_check_in',
    ];

    protected function casts(): array
    {
        return [
            'registration_deadline' => 'datetime',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'requires_check_in' => 'boolean',
        ];
    }

    public function getStatusAttribute(): string
    {
        if ($this->end_date && $this->end_date->isPast()) {
            return 'completed';
        }

        if ($this->start_date && $this->start_date->isPast()) {
            return 'in_progress';
        }

        if ($this->registration_deadline && $this->registration_deadline->isPast()) {
            return 'open';
        }

        return 'draft';
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(EventMatch::class);
    }
}
