<?php

namespace App\Enums;

/**
 * Cakupan voucher: berlaku di semua trip, satu kategori, atau satu trip saja.
 * `scope_id` menunjuk kategori/trip sesuai nilai ini.
 */
enum VoucherScope: string
{
    case Global = 'global';
    case Category = 'category';
    case Trip = 'trip';

    public function label(): string
    {
        return match ($this) {
            self::Global => 'Semua trip',
            self::Category => 'Satu kategori',
            self::Trip => 'Satu trip',
        };
    }
}
