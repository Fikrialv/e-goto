<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Approve pengajuan mitra: bikin profil vendor + akun panel sekaligus.
 *
 * Dijalankan dalam satu transaksi. Kalau pembuatan akun gagal di tengah,
 * pengajuan tidak boleh tertinggal berstatus approved tanpa vendor — admin
 * akan mengira urusannya sudah beres padahal mitra tidak bisa masuk panel.
 */
class ApproveVendorApplication
{
    /**
     * @return array{vendor: Vendor, user: User, password: ?string}
     */
    public function handle(VendorApplication $application, User $admin): array
    {
        return DB::transaction(function () use ($application, $admin): array {
            $user = User::where('email', $application->contact_email)->first();
            $passwordSementara = null;

            if ($user === null) {
                // Password acak, ditampilkan sekali ke admin untuk diteruskan ke
                // mitra lewat kanal yang sudah dipakai (WhatsApp). Tidak dikirim
                // otomatis: tidak ada layanan email transaksional di V1.5.
                $passwordSementara = Str::password(12, symbols: false);

                $user = User::create([
                    'name' => $application->contact_name,
                    'email' => $application->contact_email,
                    'password' => Hash::make($passwordSementara),
                    'role' => UserRole::Vendor,
                    'phone' => $application->contact_phone,
                ]);
            } elseif ($user->role !== UserRole::Vendor) {
                // Akun customer yang ternyata mengajukan jadi mitra dinaikkan
                // perannya, bukan digandakan — email itu unik di `users`.
                $user->update(['role' => UserRole::Vendor]);
            }

            $vendor = Vendor::create([
                'user_id' => $user->id,
                'business_name' => $application->business_name,
                'slug' => $this->slugUnik($application->business_name),
                'phone' => $application->contact_phone,
                'status' => VendorStatus::Approved,
                'approved_at' => now(),
            ]);

            $application->update([
                'status' => VendorStatus::Approved,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'vendor_id' => $vendor->id,
            ]);

            return ['vendor' => $vendor, 'user' => $user, 'password' => $passwordSementara];
        });
    }

    private function slugUnik(string $nama): string
    {
        $dasar = Str::slug($nama) ?: 'mitra';
        $slug = $dasar;
        $urutan = 2;

        while (Vendor::where('slug', $slug)->exists()) {
            $slug = $dasar.'-'.$urutan++;
        }

        return $slug;
    }
}
