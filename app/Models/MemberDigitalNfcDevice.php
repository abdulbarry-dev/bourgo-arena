<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberDigitalNfcDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'device_identifier',
        'device_model',
        'os_version',
        'supports_hce',
        'nfc_enabled',
        'setup_status',
        'is_supported',
        'is_active',
        'last_verified_at',
    ];

    protected $casts = [
        'supports_hce' => 'boolean',
        'nfc_enabled' => 'boolean',
        'is_supported' => 'boolean',
        'is_active' => 'boolean',
        'last_verified_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function isCompleted(): bool
    {
        return $this->setup_status === 'completed';
    }

    public function isRevoked(): bool
    {
        return $this->setup_status === 'revoked';
    }
}
