<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\TripImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripImage>
 */
class TripImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'path' => 'trips/'.fake()->uuid().'.jpg',
            'sort_order' => fake()->numberBetween(1, 5),
        ];
    }
}
