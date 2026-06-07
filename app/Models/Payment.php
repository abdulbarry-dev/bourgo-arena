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
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'metadata' => 'array',
        'verified_at' => 'datetime',
    ];

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
