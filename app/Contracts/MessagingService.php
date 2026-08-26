<?php

namespace App\Contracts;

use App\Models\Booking;
use App\Models\Trip;

/**
 * Batas antara aplikasi dan cara notifikasi dikirim.
 *
 * V1 diisi WhatsAppLinkService yang hanya membangun URL wa.me berisi pesan
 * siap kirim (D5) — tidak ada API, tidak ada job async. Kalau nanti pakai
 * WhatsApp Business API resmi, implementasinya diganti tanpa mengubah pemanggil.
 */
interface MessagingService
{
    /**
     * Kembalikan URL/identifier notifikasi pembayaran baru ke admin.
     */
    public function notifyAdminNewPayment(Booking $booking): string;

    /**
     * Kembalikan URL/identifier pengingat H-1 untuk satu booking (D7.6 d).
     *
     * Dikirim admin ke customer, bukan sebaliknya. Isinya wajib bebas dari
     * nomor identitas peserta — begitu pesan keluar lewat kanal ini, isinya
     * di luar kendali kita.
     */
    public function remindDayBefore(Booking $booking): string;

    /**
     * Kembalikan URL/identifier permintaan private trip untuk rombongan yang
     * melebihi batas peserta per booking (PLAN.md §5.6). Jalur sementara sampai
     * form Request Private Trip di D12 jadi.
     */
    public function requestPrivateTrip(Trip $trip): string;
}
