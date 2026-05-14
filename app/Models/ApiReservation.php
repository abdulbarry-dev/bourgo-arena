<?php

namespace App\Models;

use Database\Factories\ApiReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiReservation extends Model
{
    protected $table = 'api_reservations';

    protected $fillable = [
        'member_id',
        'activity_id',
        'activity_slot_id',
        'date',
        'starts_at',
        'ends_at',
        'price',
        'status',
        'payment_status',
        'qr_code',
        'cancelled_at',
    ];

    protected $casts = [
        'date' => 'date',
        'cancelled_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(ActivitySlot::class, 'activity_slot_id');
    }

    /** @use HasFactory<ApiReservationFactory> */
    use HasFactory;
}
