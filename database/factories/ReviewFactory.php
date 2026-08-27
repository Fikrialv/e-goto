<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->sentence(),
            'status' => ReviewStatus::Published,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ReviewStatus::Hidden]);
    }
}
