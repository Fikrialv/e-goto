<?php

namespace Database\Seeders;

use App\Enums\IdType;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Domestik', 'slug' => 'domestik', 'id_requirement' => IdType::Nik, 'is_active' => true, 'sort_order' => 1],
            ['name' => 'Pendakian', 'slug' => 'pendakian', 'id_requirement' => IdType::Nik, 'is_active' => true, 'sort_order' => 2],
            ['name' => 'Pantai', 'slug' => 'pantai', 'id_requirement' => IdType::None, 'is_active' => true, 'sort_order' => 3],
            ['name' => 'Keliling Kota', 'slug' => 'keliling-kota', 'id_requirement' => IdType::None, 'is_active' => true, 'sort_order' => 4],
            ['name' => 'Aktivitas', 'slug' => 'aktivitas', 'id_requirement' => IdType::None, 'is_active' => true, 'sort_order' => 5],
            // Ditutup sementara sesuai GUIDE.md — kategorinya tetap ada supaya
            // trip lama tidak kehilangan induk saat nanti dibuka lagi.
            ['name' => 'Internasional', 'slug' => 'internasional', 'id_requirement' => IdType::Passport, 'is_active' => false, 'sort_order' => 6],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
