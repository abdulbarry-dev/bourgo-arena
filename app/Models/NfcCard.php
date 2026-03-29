<?php

namespace App\Models;

use Database\Factories\NfcCardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NfcCard extends Model
{
    /** @use HasFactory<NfcCardFactory> */
    use HasFactory;

    protected $fillable = [
        'member_id',
        'uid',
        'status',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function assign(Member $member, int $assignedBy): void
    {
        $this->update([
            'member_id' => $member->id,
            'status' => 'active',
            'assigned_by' => $assignedBy,
            'assigned_at' => now(),
        ]);
    }

    public function suspend(): void
    {
        $this->update([
            'status' => 'suspended',
        ]);
    }

    public function reactivate(): void
    {
        $this->update([
            'status' => 'active',
        ]);
    }

    public function markLost(): void
    {
        $this->update([
            'status' => 'lost',
        ]);
    }
}
