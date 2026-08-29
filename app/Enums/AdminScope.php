<?php

namespace App\Enums;

/**
 * Pembagian tugas di dalam role Admin (GUIDE.md "Pemecahan permission admin").
 *
 * Ini SENGAJA bukan role baru di `UserRole`. Role menentukan panel mana yang
 * boleh dibuka; scope menentukan layar mana di dalam panel admin yang terlihat.
 * Menambah role ketiga akan menyeret ulang seluruh `canAccessPanel()` dan
 * middleware `role:` yang sudah stabil sejak D1, demi pembagian yang cakupannya
 * cuma di dalam satu panel.
 *
 * `null` (tanpa scope) berarti akses penuh — itu keadaan pemilik project. Scope
 * hanya dipasang ke admin tambahan, dan memasangnya MEMPERSEMPIT, tidak pernah
 * memperluas.
 */
enum AdminScope: string
{
    /** Antrean pembayaran, pengingat H-1, pengajuan mitra, moderasi ulasan. */
    case PaymentCs = 'payment_cs';

    /** Trip, kategori, dan voucher. */
    case TripManager = 'trip_manager';

    public function label(): string
    {
        return match ($this) {
            self::PaymentCs => 'Verifikator Pembayaran + CS',
            self::TripManager => 'Manajer Trip & Mitra',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PaymentCs => 'Verifikasi bukti bayar, pengingat H-1, tinjau pengajuan mitra, moderasi ulasan.',
            self::TripManager => 'Kelola trip, kategori, dan voucher.',
        };
    }

    /**
     * @return array<int, self>
     */
    public static function pilihanUntukSelect(): array
    {
        return self::cases();
    }
}
