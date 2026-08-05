<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\TripSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripSchedule>
 */
class TripScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+3 days', '+3 months');

        return [
            'trip_id' => Trip::factory(),
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+2 days'),
            'quota' => fake()->numberBetween(10, 30),
            'booked_count' => 0,
            'status' => 'published',
        ];
    }

    /**
     * Jadwal yang sudah lewat — dipakai untuk menguji bahwa halaman publik
     * tidak menampilkannya.
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->subWeek(),
            'end_date' => now()->subWeek()->addDays(2),
        ]);
    }

    /**
     * Kuota habis: booked_count menyentuh quota.
     */
    public function soldOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'booked_count' => $attributes['quota'] ?? 10,
        ]);
    }
}
