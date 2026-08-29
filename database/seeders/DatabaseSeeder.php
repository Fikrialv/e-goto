<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            DemoUserSeeder::class,
            // Sebelum DemoTripSeeder: trip demo ditempelkan ke mitra ini.
            DemoVendorSeeder::class,
            DemoTripSeeder::class,
        ]);
    }
}
