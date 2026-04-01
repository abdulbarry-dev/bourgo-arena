<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'subscription_id',
        'action',
        'reason',
        'from_member_id',
        'to_member_id',
        'performed_by',
        'performed_at',
        'metadata',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function fromMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'from_member_id');
    }

    public function toMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'to_member_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
