<?php

namespace Database\Factories\Api\Reservations;

use App\Models\Activity;
use App\Models\ActivitySession;
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
        $session = ActivitySession::factory()->create();

        return [
            'member_id' => Member::factory(),
            'activity_id' => $session->activity_id,
            'activity_session_id' => $session->id,
            'date' => now()->addDay()->toDateString(),
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
            'payment_status' => 'pending',
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

    public function forSession(ActivitySession $session): static
    {
        return $this->state(fn (): array => [
            'activity_id' => $session->activity_id,
            'activity_session_id' => $session->id,
            'date' => now()->addDay()->toDateString(),
            'price' => $session->activity?->base_price ?? 0,
        ]);
    }
}
