<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\TripOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripOption>
 */
class TripOptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'name' => fake()->randomElement(['Camping', 'Tubing', 'Playground', 'Sewa alat']),
            'description' => fake()->sentence(),
            'extra_price' => fake()->numberBetween(50, 300) * 1000,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
