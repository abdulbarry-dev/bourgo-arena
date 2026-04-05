<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CourtSlot extends Model
{
    protected $fillable = [
        'court_type',
        'date',
        'starts_at',
        'ends_at',
        'member_id',
    ];

    protected $casts = [
        'date' => 'date',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }
}
