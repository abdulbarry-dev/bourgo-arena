<?php

namespace App\Models;

use Database\Factories\Shared\Notifications\NotificationLogFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected static function newFactory(): Factory
    {
        return NotificationLogFactory::new();
    }

    /** @use HasFactory<NotificationLogFactory> */
    use HasFactory;

    protected $fillable = [
        'notification_type_id',
        'member_id',
        'channel',
        'subject',
        'body',
        'status',
        'sent_at',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function notificationType(): BelongsTo
    {
        return $this->belongsTo(NotificationType::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
