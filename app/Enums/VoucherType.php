<?php

namespace App\Enums;

/**
 * Bentuk potongan voucher. Enum, bukan string bebas (CLAUDE.md §4) — nilai ini
 * menentukan cara menghitung uang, jadi salah ketik tidak boleh mungkin.
 */
enum VoucherType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Persen dari subtotal',
            self::Fixed => 'Potongan rupiah tetap',
        };
    }
}
