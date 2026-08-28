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
            ['name' => 'Domestik', 'slug' => 'domestik', 'icon' => 'map', 'id_requirement' => IdType::Nik, 'is_active' => true, 'sort_order' => 1],
            ['name' => 'Pendakian', 'slug' => 'pendakian', 'icon' => 'mountain', 'id_requirement' => IdType::Nik, 'is_active' => true, 'sort_order' => 2, 'gear_checklist' => ['Tas carrier 40L+', 'Jaket gunung & jas hujan', 'Sepatu trekking', 'Sleeping bag', 'Headlamp + baterai cadangan', 'Air minum 2 liter', 'Obat pribadi']],
            ['name' => 'Pantai', 'slug' => 'pantai', 'icon' => 'waves', 'id_requirement' => IdType::None, 'is_active' => true, 'sort_order' => 3, 'gear_checklist' => ['Sunblock', 'Baju ganti & handuk cepat kering', 'Sandal gunung', 'Dry bag untuk HP', 'Topi atau kacamata hitam']],
            ['name' => 'Keliling Kota', 'slug' => 'keliling-kota', 'icon' => 'building-2', 'id_requirement' => IdType::None, 'is_active' => true, 'sort_order' => 4],
            ['name' => 'Aktivitas', 'slug' => 'aktivitas', 'icon' => 'compass', 'id_requirement' => IdType::None, 'is_active' => true, 'sort_order' => 5],
            // Ditutup sementara sesuai GUIDE.md — kategorinya tetap ada supaya
            // trip lama tidak kehilangan induk saat nanti dibuka lagi.
            ['name' => 'Internasional', 'slug' => 'internasional', 'icon' => 'globe', 'id_requirement' => IdType::Passport, 'is_active' => false, 'sort_order' => 6],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
