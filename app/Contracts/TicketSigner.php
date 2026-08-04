<?php

namespace App\Contracts;

use App\Models\Ticket;

/**
 * Penandatangan tiket — mencegah tiket palsu dan tiket yang diubah isinya.
 *
 * V1 diisi HmacTicketSigner: HMAC-SHA256 atas (token|booking_code|participant_id)
 * dengan APP_KEY sebagai kunci (D6). Verifikasi wajib pakai perbandingan
 * waktu-konstan, bukan `===`, supaya tidak bocor lewat timing attack.
 */
interface TicketSigner
{
    public function sign(Ticket $ticket): string;

    public function verify(Ticket $ticket, string $signature): bool;
}
