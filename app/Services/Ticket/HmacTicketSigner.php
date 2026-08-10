<?php

namespace App\Services\Ticket;

use App\Contracts\TicketSigner;
use App\Models\Ticket;

/**
 * Tanda tangan tiket: HMAC-SHA256 atas (token|booking_code|participant_id)
 * dengan APP_KEY sebagai kunci.
 *
 * Kenapa bukan sekadar token acak di database: token yang bocor/diubah satu
 * karakter akan gagal dicocokkan tanpa perlu query, dan penyerang tidak bisa
 * mengarang tiket baru karena tidak memegang APP_KEY.
 */
class HmacTicketSigner implements TicketSigner
{
    public function sign(Ticket $ticket): string
    {
        return hash_hmac('sha256', $this->payload($ticket), $this->key());
    }

    /**
     * Perbandingan waktu-konstan — `===` pada string rahasia berhenti di byte
     * pertama yang beda, dan selisih waktunya cukup untuk menebak tanda tangan
     * karakter demi karakter.
     */
    public function verify(Ticket $ticket, string $signature): bool
    {
        return hash_equals($this->sign($ticket), $signature);
    }

    private function payload(Ticket $ticket): string
    {
        return implode('|', [
            $ticket->token,
            $ticket->booking->code,
            $ticket->participant_id,
        ]);
    }

    private function key(): string
    {
        return (string) config('app.key');
    }
}
