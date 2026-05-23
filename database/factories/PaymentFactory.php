<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'reservation_id' => null,
            'subscription_id' => null,
            'driver' => 'konnect',
            'gateway' => null,
            'type' => 'reservation_deposit',
            'amount' => $this->faker->randomFloat(3, 1, 50),
            'currency' => 'TND',
            'status' => 'pending',
            'payment_reference' => 'pay_'.substr(md5($this->faker->uuid), 0, 12),
            'gateway_transaction_id' => null,
            'metadata' => null,
        ];
    }
}
