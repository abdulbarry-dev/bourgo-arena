<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'admin_id',
        'type',
        'amount',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
