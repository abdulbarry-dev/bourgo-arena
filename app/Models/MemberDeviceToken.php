<?php

namespace App\Models;

use Database\Factories\MemberDeviceTokenFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberDeviceToken extends Model
{
    protected static function newFactory(): Factory
    {
        return MemberDeviceTokenFactory::new();
    }

    /** @use HasFactory<MemberDeviceTokenFactory> */
    use HasFactory;

    protected $fillable = [
        'member_id',
        'token',
        'provider',
        'device_type',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'last_used_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
