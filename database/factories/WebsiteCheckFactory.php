<?php

namespace Database\Factories;

use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebsiteCheck>
 */
class WebsiteCheckFactory extends Factory
{
    public function definition(): array
    {
        $isUp = $this->faker->boolean(80); // 80% chance of being up

        return [
            'website_id' => Website::factory(),
            'is_up' => $isUp,
            'response_time_ms' => $isUp ? $this->faker->numberBetween(100, 3000) : null,
            'status_code' => $isUp ? $this->faker->randomElement([200, 201, 301, 302]) : $this->faker->randomElement([404, 500, 503]),
            'error_message' => $isUp ? null : $this->faker->sentence(),
            'checked_at' => now(),
        ];
    }

    public function up(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_up' => true,
            'status_code' => 200,
            'response_time_ms' => $this->faker->numberBetween(100, 2000),
            'error_message' => null,
        ]);
    }

    public function down(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_up' => false,
            'status_code' => $this->faker->randomElement([404, 500, 503]),
            'response_time_ms' => $this->faker->numberBetween(100, 5000),
            'error_message' => $this->faker->sentence(),
        ]);
    }

    public function timeout(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_up' => false,
            'status_code' => null,
            'response_time_ms' => 10000,
            'error_message' => 'Connection timeout',
        ]);
    }
}
