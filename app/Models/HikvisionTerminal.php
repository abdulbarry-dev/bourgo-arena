<?php

namespace App\Models;

use Database\Factories\HikvisionTerminalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HikvisionTerminal extends Model
{
    /** @use HasFactory<HikvisionTerminalFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'serial_number',
        'location',
        'terminal_type',
        'api_token',
        'status',
        'operating_mode',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function setApiTokenAttribute(?string $token): void
    {
        if ($token === null || $token === '') {
            $this->attributes['api_token'] = $token;

            return;
        }

        if (Str::length($token) === 64 && ctype_xdigit($token)) {
            $this->attributes['api_token'] = $token;

            return;
        }

        $this->attributes['api_token'] = hash('sha256', $token);
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function markSeen(): void
    {
        $this->update([
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
    }

    public function markOffline(): void
    {
        $this->update([
            'status' => 'offline',
        ]);
    }

    public function decommission(): void
    {
        $this->update([
            'status' => 'decommissioned',
        ]);
    }
}
