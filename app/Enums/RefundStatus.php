<?php

namespace App\Enums;

/**
 * Perjalanan satu pengajuan refund.
 *
 * `Selesai` sengaja terpisah dari `Disetujui`: menyetujui adalah keputusan,
 * mengirim uang adalah pekerjaan. Menggabungkannya berarti admin kehilangan
 * daftar "sudah disetujui tapi uangnya belum ditransfer" — persis daftar yang
 * paling mahal kalau terlewat.
 */
enum RefundStatus: string
{
    case Diajukan = 'diajukan';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Diajukan',
            self::Disetujui => 'Disetujui, menunggu proses',
            self::Ditolak => 'Ditolak',
            self::Selesai => 'Selesai',
        };
    }

    /** Warna badge Filament & x-status-badge. */
    public function tone(): string
    {
        return match ($this) {
            self::Diajukan => 'warning',
            self::Disetujui => 'info',
            self::Ditolak => 'danger',
            self::Selesai => 'success',
        };
    }

    /** Sudah tidak bisa diubah customer maupun admin. */
    public function final(): bool
    {
        return match ($this) {
            self::Ditolak, self::Selesai => true,
            self::Diajukan, self::Disetujui => false,
        };
    }
}
