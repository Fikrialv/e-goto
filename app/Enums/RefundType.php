<?php

namespace App\Enums;

/**
 * Tiga opsi yang menjadi hak customer saat trip dibatalkan penyelenggara, kuota
 * minimum tidak tercapai, atau terjadi force majeure (GUIDE.md "Kebijakan
 * Refund", revisi 2026-08-24).
 *
 * Enum ini MENJALANKAN kebijakan yang sudah ada, bukan membuat kebijakan baru.
 * Tidak ada opsi keempat, dan tidak ada opsi "hangus" — kebijakannya menyebut
 * customer wajib mendapat salah satu dari tiga ini.
 */
enum RefundType: string
{
    case Refund100 = 'refund_100';
    case GantiTrip = 'ganti_trip';
    case Waitlist = 'waitlist';

    public function label(): string
    {
        return match ($this) {
            self::Refund100 => 'Refund 100%',
            self::GantiTrip => 'Pindah ke trip/jadwal lain',
            self::Waitlist => 'Masuk waitlist jadwal berikutnya',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Refund100 => 'Uang dikembalikan penuh ke rekening yang sama dengan pembayaran awal.',
            self::GantiTrip => 'Kalau harga trip penggantinya berbeda, selisihnya dibicarakan dulu dengan admin.',
            self::Waitlist => 'Kamu dicatat lebih dulu saat jadwal trip yang sama dibuka lagi.',
        };
    }
}
