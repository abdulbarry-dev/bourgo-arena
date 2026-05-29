<?php

namespace Database\Factories\Api\Reservations;

use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiReservation>
 */
class ApiReservationFactory extends Factory
{
    protected $model = ApiReservation::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'activity_id' => Activity::factory(),
            'activity_slot_id' => ActivitySlot::factory(),
            'date' => now()->addDay()->toDateString(),
            'starts_at' => '10:00:00',
            'ends_at' => '11:00:00',
            'price' => fake()->randomFloat(2, 10, 100),
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'qr_code' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => 'cancelled',
            'payment_status' => 'refunded',
            'cancelled_at' => now(),
        ]);
    }

    public function forActivity(Activity $activity): static
    {
        return $this->state(fn (): array => [
            'activity_id' => $activity->id,
            'price' => $activity->base_price,
        ]);
    }

    public function forSlot(ActivitySlot $slot): static
    {
        return $this->state(fn (): array => [
            'activity_id' => $slot->activity_id,
            'activity_slot_id' => $slot->id,
            'date' => $slot->date->toDateString(),
            'starts_at' => $slot->starts_at,
            'ends_at' => $slot->ends_at,
            'price' => $slot->activity?->base_price ?? 0,
        ]);
    }
}
