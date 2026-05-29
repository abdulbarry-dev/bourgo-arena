<?php

namespace App\Models;

use Database\Factories\Dashboard\Events\EventMatchFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventMatch extends Model
{
    protected static function newFactory(): Factory
    {
        return EventMatchFactory::new();
    }

    /** @use HasFactory<EventMatchFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id', 'round', 'match_number', 'participant1_id', 'participant2_id',
        'scheduled_at', 'winner_id', 'score', 'status', 'next_match_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function participant1()
    {
        return $this->belongsTo(EventParticipant::class, 'participant1_id');
    }

    public function participant2()
    {
        return $this->belongsTo(EventParticipant::class, 'participant2_id');
    }

    public function winner()
    {
        return $this->belongsTo(EventParticipant::class, 'winner_id');
    }

    public function nextMatch()
    {
        return $this->belongsTo(EventMatch::class, 'next_match_id');
    }
}
