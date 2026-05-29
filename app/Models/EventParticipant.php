<?php

namespace App\Models;

use Database\Factories\EventParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventParticipant extends Model
{
    /** @use HasFactory<EventParticipantFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id', 'user_id', 'seed_number', 'has_checked_in', 'status', 'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'has_checked_in' => 'boolean',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
