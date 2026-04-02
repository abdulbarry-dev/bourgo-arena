<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberNotification extends Model
{
    protected $fillable = [
        'member_id',
        'type',
        'title',
        'message',
        'channel',
        'status',
        'is_read',
        'metadata',
        'delivered_at',
    ];

    protected $casts = [
        'is_read' => 'bool',
        'metadata' => 'array',
        'delivered_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
