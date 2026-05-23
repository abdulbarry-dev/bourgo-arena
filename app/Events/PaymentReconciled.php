<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReconciled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Payment $payment;

    public array $payload;

    public function __construct(Payment $payment, array $payload = [])
    {
        $this->payment = $payment;
        $this->payload = $payload;
    }
}
