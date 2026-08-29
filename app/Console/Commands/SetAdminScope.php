<?php

namespace App\Console\Commands;

use App\Enums\AdminScope;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Menetapkan pembagian tugas satu admin.
 *
 * Sengaja lewat Artisan, bukan lewat layar di panel: layar yang bisa mengubah
 * hak akses adalah layar yang paling berharga untuk dibajak, dan menambahnya
 * berarti menambah permukaan serang justru di panel yang sedang kita batasi.
 * Perubahan hak akses jarang terjadi dan selalu dilakukan pemilik project yang
 * punya akses shell.
 */
class SetAdminScope extends Command
{
    protected $signature = 'admin:scope
        {email : Email admin yang mau diatur}
        {scope? : payment_cs, trip_manager, atau kosongkan untuk akses penuh}';

    protected $description = 'Tetapkan pembagian tugas admin (kosong = akses penuh)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('User dengan email itu tidak ada.');

            return self::FAILURE;
        }

        if ($user->role !== UserRole::Admin) {
            // Scope tidak pernah memberi akses; ia hanya mempersempit. Memasangnya
            // ke non-admin akan terbaca seolah user itu punya hak yang sebenarnya
            // tidak pernah ia punya.
            $this->error("User itu berrole {$user->role->value}, bukan admin. Scope tidak berlaku untuknya.");

            return self::FAILURE;
        }

        $masukan = $this->argument('scope');
        $scope = $masukan === null ? null : AdminScope::tryFrom($masukan);

        if ($masukan !== null && $scope === null) {
            $this->error('Scope tidak dikenal. Pilihan: '.implode(', ', array_column(AdminScope::cases(), 'value')));

            return self::FAILURE;
        }

        $user->update(['admin_scope' => $scope]);

        $this->info(sprintf(
            '%s sekarang: %s',
            $user->email,
            $scope?->label() ?? 'akses penuh (pemilik project)',
        ));

        return self::SUCCESS;
    }
}
