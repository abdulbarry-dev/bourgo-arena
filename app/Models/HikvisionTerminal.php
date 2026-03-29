<?php

namespace App\Models;

use Database\Factories\HikvisionTerminalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

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
