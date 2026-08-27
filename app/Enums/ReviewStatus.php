<?php

namespace App\Enums;

/**
 * Review yang kasar atau memuat data pribadi disembunyikan admin, bukan
 * dihapus — jejaknya tetap ada kalau suatu saat keputusannya dipersoalkan.
 */
enum ReviewStatus: string
{
    case Published = 'published';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Published => 'Tampil',
            self::Hidden => 'Disembunyikan',
        };
    }
}
