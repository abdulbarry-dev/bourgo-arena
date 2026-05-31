<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LoyaltyPoint extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'points',
        'transaction_type',
        'source_type',
        'source_id',
        'idempotency_key',
        'created_at',
    ];

    protected $casts = [
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
