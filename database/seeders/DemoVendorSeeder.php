<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

/**
 * Profil usaha untuk akun mitra demo.
 *
 * Sebelum ini, `vendor@egoto.test` punya akun panel tapi tidak punya baris
 * `vendors` sama sekali — panel mitranya kosong dan halaman publik
 * `/mitra/{slug}` tidak punya satu pun contoh yang bisa dibuka. Fiturnya lulus
 * test, tapi tidak ada yang bisa dilihat mata di localhost.
 *
 * Jalan setelah DemoUserSeeder (akunnya) dan sebelum DemoTripSeeder (yang
 * menempelkan trip demo ke mitra ini).
 */
class DemoVendorSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'vendor@egoto.test')
            ->where('role', UserRole::Vendor)
            ->first();

        if ($user === null) {
            return;
        }

        Vendor::updateOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => 'Rimba Sejati Adventure',
                'slug' => 'rimba-sejati-adventure',
                'description' => 'Bawa rombongan kecil ke gunung dan pantai sejak 2015. Pemandu lokal, kuota dibatasi, jalur yang sudah dilewati berkali-kali.',
                'phone' => '081234567890',
                'address' => 'Semarang, Jawa Tengah',
                'status' => VendorStatus::Approved,
                'approved_at' => now()->subMonths(8),
            ],
        );
    }
}
