<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReconcileFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $paymentId;

    public string $reason;

    public array $payload;

    public function __construct(int $paymentId, string $reason = '', array $payload = [])
    {
        $this->paymentId = $paymentId;
        $this->reason = $reason;
        $this->payload = $payload;
    }
}
