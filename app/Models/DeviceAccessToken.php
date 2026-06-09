<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceAccessToken extends Model
{
    protected $fillable = [
        'device_id',
        'token',
        'device_fingerprint',
        'platform',
        'app_version',
        'integrity_passed',
        'integrity_payload',
        'ip_address',
        'member_id',
        'last_verified_at',
        'last_used_at',
        'expires_at',
        'is_revoked',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'device_fingerprint' => 'array',
            'integrity_passed' => 'boolean',
            'is_revoked' => 'boolean',
            'last_verified_at' => 'datetime',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_revoked', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function scopeForDevice($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeForToken($query, string $token)
    {
        return $query->where('token', $token);
    }
}
