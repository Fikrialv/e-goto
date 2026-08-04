<?php

namespace App\Contracts;

use App\Models\Booking;

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
}
