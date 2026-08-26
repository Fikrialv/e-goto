<?php

namespace App\Enums;

/**
 * Tingkat kesulitan fisik satu trip (PLAN.md §4, D7.6 item c).
 *
 * Label sengaja berbentuk kalimat manusia, bukan nama case-nya: orang memilih
 * "cocok untuk pemula", bukan "pemula" sebagai istilah teknis. Salah menilai
 * beban fisik bukan sekadar bikin kecewa, jadi keterangannya ikut dibawa.
 */
enum TripDifficulty: string
{
    case Pemula = 'pemula';
    case Menengah = 'menengah';
    case Lanjutan = 'lanjutan';

    public function label(): string
    {
        return match ($this) {
            self::Pemula => 'Cocok untuk pemula',
            self::Menengah => 'Butuh persiapan fisik',
            self::Lanjutan => 'Untuk yang sudah terbiasa',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Pemula => 'Jalur landai, durasi pendek. Belum pernah ikut trip sejenis pun aman.',
            self::Menengah => 'Ada tanjakan panjang atau durasi seharian. Latihan jalan kaki dulu sebelum berangkat.',
            self::Lanjutan => 'Medan berat, durasi panjang, cuaca bisa ekstrem. Sudah pernah trip sejenis sangat disarankan.',
        };
    }
}
