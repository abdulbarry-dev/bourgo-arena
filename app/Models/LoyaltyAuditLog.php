<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LoyaltyAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'action',
        'points_changed',
        'balance_before',
        'balance_after',
        'source_type',
        'source_id',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'json',
        'created_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
