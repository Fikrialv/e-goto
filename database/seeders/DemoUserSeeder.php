<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun demo untuk pengembangan lokal.
 *
 * Password di sini sengaja dibuat mudah diingat karena hanya dipakai di mesin
 * developer. Sebelum production, akun ini WAJIB diganti/dinonaktifkan —
 * tercatat sebagai item D7 di docs/update.md, jangan sampai ikut terbawa.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['email' => 'admin@egoto.test', 'name' => 'Admin E-GOTO', 'role' => UserRole::Admin],
            ['email' => 'vendor@egoto.test', 'name' => 'Vendor Demo', 'role' => UserRole::Vendor],
            ['email' => 'customer@egoto.test', 'name' => 'Customer Demo', 'role' => UserRole::Customer],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    ...$account,
                    // Cast 'hashed' di model User yang meng-hash nilai ini.
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
