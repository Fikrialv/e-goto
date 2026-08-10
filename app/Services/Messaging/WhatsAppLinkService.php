<?php

namespace App\Services\Messaging;

use App\Contracts\MessagingService;
use App\Models\Booking;

/**
 * Notifikasi V1: link wa.me berisi pesan siap kirim.
 *
 * Customer yang menekan tombolnya, bukan server yang mengirim — tidak ada API,
 * tidak ada worker. Pesannya sengaja hanya memuat kode booking dan nominal:
 * data peserta (apalagi NIK) tidak boleh ikut keluar lewat kanal ini.
 */
class WhatsAppLinkService implements MessagingService
{
    public function notifyAdminNewPayment(Booking $booking): string
    {
        $pesan = sprintf(
            "Halo Admin E-GOTO, saya sudah mengunggah bukti pembayaran.\n\nKode booking: %s\nNominal: Rp%s\n\nMohon dicek, terima kasih.",
            $booking->code,
            number_format($booking->total_amount, 0, ',', '.'),
        );

        return 'https://wa.me/'.config('booking.admin_whatsapp').'?text='.rawurlencode($pesan);
    }
}
