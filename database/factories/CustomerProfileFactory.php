<?php

namespace Database\Factories;

use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerProfile>
 */
class CustomerProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->customer(),
            'full_name' => fake()->name(),
            'dob' => fake()->dateTimeBetween('-45 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['laki-laki', 'perempuan']),
            'address' => fake()->address(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->numerify('08##########'),
        ];
    }
}
