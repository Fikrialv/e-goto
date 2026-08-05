<?php

namespace Database\Factories;

use App\Enums\TripStatus;
use App\Models\Category;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'vendor_id' => null,
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(),
            'itinerary' => fake()->paragraph(),
            'includes' => fake()->sentence(),
            'excludes' => fake()->sentence(),
            'meeting_point' => fake()->city(),
            'cover_image' => null,
            'status' => TripStatus::Draft,
            'is_featured' => false,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TripStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => ['is_featured' => true]);
    }
}
