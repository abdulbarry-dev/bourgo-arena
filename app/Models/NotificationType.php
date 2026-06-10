<?php

namespace App\Models;

use Database\Factories\Shared\Notifications\NotificationTypeFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NotificationType extends Model
{
    protected static function newFactory(): Factory
    {
        return NotificationTypeFactory::new();
    }

    /** @use HasFactory<NotificationTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'category',
        'push_enabled',
        'email_enabled',
        'sms_enabled',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'push_enabled' => 'bool',
        'email_enabled' => 'bool',
        'sms_enabled' => 'bool',
        'is_active' => 'bool',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (NotificationType $type) {
            if (empty($type->slug)) {
                $type->slug = Str::slug($type->name);
            }
        });
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }
}
