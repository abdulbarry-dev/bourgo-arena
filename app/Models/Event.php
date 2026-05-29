<?php

namespace App\Models;

use Database\Factories\Dashboard\Events\EventFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected static function newFactory(): Factory
    {
        return EventFactory::new();
    }

    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'sport_type', 'format', 'max_participants',
        'registration_deadline', 'start_date', 'end_date', 'requires_check_in', 'status',
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

    public function participants()
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function matches()
    {
        return $this->hasMany(EventMatch::class);
    }
}
