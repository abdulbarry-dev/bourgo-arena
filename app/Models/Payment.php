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
        'currency',
        'status',
        'payment_reference',
        'gateway_transaction_id',
        'metadata',
        'verified_at',
        'reconciled_by',
        'reconciled_at',
        'refunded_by',
        'refunded_at',
        'refund_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'metadata' => 'array',
        'verified_at' => 'datetime',
        'reconciled_at' => 'datetime',
        'refunded_at' => 'datetime',
        'refund_amount' => 'decimal:3',
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

    /**
     * Admin who reconciled this payment (via reconciled_by field).
     */
    public function reconciledBy()
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    /**
     * Admin who refunded this payment (via refunded_by field).
     */
    public function refundedBy()
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    /**
     * Reconciliation history (reconciled/refunded events).
     */
    public function reconciliations()
    {
        return $this->hasMany(PaymentReconciliation::class);
    }
}
