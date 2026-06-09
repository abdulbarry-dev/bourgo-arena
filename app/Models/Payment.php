<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'reservation_id',
        'subscription_id',
        'driver',
        'gateway',
        'type',
        'amount',
        'status',
        'payment_reference',
        'gateway_transaction_id',
        'metadata',
        'verified_at',
        'ip_address',
        'country_code',
        'city',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'metadata' => 'array',
        'verified_at' => 'datetime',
    ];

    public function getDescriptionAttribute(): string
    {
        if ($this->type === 'subscription' && $this->subscription) {
            return __('Subscription: :plan', ['plan' => $this->subscription->plan?->name]);
        }

        if ($this->type === 'reservation' && $this->reservation) {
            return __('Reservation: :activity', ['activity' => $this->reservation->activity?->name]);
        }

        return ucfirst($this->type);
    }

    public function getReceiptUrlAttribute(): ?string
    {
        if ($this->type === 'subscription' && $this->subscription?->receipt_path) {
            return asset('storage/'.$this->subscription->receipt_path);
        }

        return null;
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function reservation()
    {
        return $this->belongsTo(ApiReservation::class, 'reservation_id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
