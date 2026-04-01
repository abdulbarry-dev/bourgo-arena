<?php

namespace App\Models;

use Database\Factories\CheckInEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckInEvent extends Model
{
    /** @use HasFactory<CheckInEventFactory> */
    use HasFactory;

    protected $fillable = [
        'member_id',
        'card_uid',
        'terminal_id',
        'result',
        'denial_reason',
        'is_suspicious',
        'checked_in_at',
    ];

    protected $casts = [
        'is_suspicious' => 'boolean',
        'checked_in_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public const UPDATED_AT = null;

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(HikvisionTerminal::class, 'terminal_id');
    }
}
