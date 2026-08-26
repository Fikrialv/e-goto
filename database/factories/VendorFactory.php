<?php

namespace Database\Factories;

use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = fake()->unique()->company();

        return [
            'user_id' => User::factory()->vendor(),
            'business_name' => $nama,
            'slug' => Str::slug($nama),
            'phone' => '08'.fake()->numerify('##########'),
            'status' => VendorStatus::Approved,
            'approved_at' => now(),
        ];
    }
}
