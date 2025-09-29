<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Website>
 */
class WebsiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'url' => $this->faker->url(),
            'name' => $this->faker->words(3, true),
            'is_active' => true,
            'is_up' => true,
            'last_checked_at' => null,
            'last_downtime_at' => null,
            'response_time_ms' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function down(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_up' => false,
            'last_downtime_at' => now(),
        ]);
    }

    public function withResponseTime(int $ms): static
    {
        return $this->state(fn (array $attributes) => [
            'response_time_ms' => $ms,
            'last_checked_at' => now(),
        ]);
    }
}
