<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'user_id',
        'reservation_id',
        'amount',
        'currency',
        'payment_gateway',
        'transaction_status',
        'external_gateway_reference',
        'reservation_details',
        'user_information',
        'ip_address',
        'user_agent',
        'request_payload',
        'response_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:3',
            'reservation_details' => 'encrypted:array',
            'user_information' => 'encrypted:array',
            'request_payload' => 'encrypted:array',
            'response_payload' => 'encrypted:array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
