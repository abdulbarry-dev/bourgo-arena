<?php

namespace Database\Factories;

use App\Models\HikvisionTerminal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HikvisionTerminal>
 */
class HikvisionTerminalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Terminal '.fake()->numberBetween(1, 99),
            'ip_address' => fake()->ipv4(),
            'serial_number' => strtoupper(Str::random(16)),
            'location' => fake()->randomElement(['Main Entrance', 'Exit Gate']),
            'terminal_type' => fake()->randomElement(['entry', 'exit']),
            'api_token' => Str::random(64),
            'status' => 'offline',
            'last_seen_at' => null,
        ];
    }

    public function online(): static
    {
        return $this->state([
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
    }
}
