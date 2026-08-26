<?php

namespace Database\Factories;

use App\Enums\VendorStatus;
use App\Models\VendorApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorApplication>
 */
class VendorApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_name' => fake()->company(),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->unique()->safeEmail(),
            'contact_phone' => '08'.fake()->numerify('##########'),
            'experience' => fake()->sentence(),
            'documents' => [],
            'status' => VendorStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VendorStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }
}
