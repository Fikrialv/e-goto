<?php

namespace Database\Factories;

use App\Models\TripPrice;
use App\Models\TripSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripPrice>
 */
class TripPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_schedule_id' => TripSchedule::factory(),
            'label' => 'Reguler',
            // Rupiah utuh, kelipatan 50 ribu — meniru harga trip nyata.
            'price' => fake()->numberBetween(6, 40) * 50_000,
            'min_pax' => 1,
            'max_pax' => null,
        ];
    }
}
