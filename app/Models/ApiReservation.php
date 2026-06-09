<?php

namespace App\Models;

use Database\Factories\Api\Reservations\ApiReservationFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ApiReservation extends Model
{
    protected $table = 'api_reservations';

    protected $fillable = [
        'member_id',
        'activity_id',
        'activity_session_id',
        'date',
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

    public function session(): BelongsTo
    {
        return $this->belongsTo(ActivitySession::class, 'activity_session_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'reservation_id');
    }

    protected static function newFactory(): Factory
    {
        return ApiReservationFactory::new();
    }

    public function isRefundable(): bool
    {
        if (! $this->date || ! $this->session) {
            return false;
        }

        return Carbon::parse($this->date->format('Y-m-d').' '.$this->session->starts_at)->isFuture();
    }

    /** @use HasFactory<ApiReservationFactory> */
    use HasFactory;
}
