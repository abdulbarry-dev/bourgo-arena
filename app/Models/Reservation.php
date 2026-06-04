<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_id',
        'activity_time_slot_id',
        'reservation_status',
        'payment_status',
        'deposit_amount',
        'full_amount',
        'payment_gateway',
        'transaction_reference',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'deposit_amount' => 'decimal:3',
            'full_amount' => 'decimal:3',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(ActivityTimeSlot::class, 'activity_time_slot_id');
    }
}
