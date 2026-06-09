<?php

namespace Database\Factories\Shared\Activities;

use App\Models\Activity;
use App\Models\ActivitySlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivitySlot>
 */
class ActivitySlotFactory extends Factory
{
    protected $model = ActivitySlot::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'starts_at' => '10:00:00',
            'ends_at' => '11:00:00',
            'capacity' => 10,
            'is_available' => true,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (): array => [
            'is_available' => false,
        ]);
    }

    public function forActivity(Activity $activity): static
    {
        return $this->state(fn (): array => [
            'activity_id' => $activity->id,
        ]);
    }
}
