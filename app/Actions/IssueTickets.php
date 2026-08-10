<?php

namespace App\Actions;

use App\Contracts\TicketSigner;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Menerbitkan satu tiket per peserta setelah pembayaran disetujui.
 *
 * Idempoten: dipanggil dua kali (mis. admin menekan approve pada dua tab) tidak
 * menggandakan tiket — peserta yang sudah punya tiket dilewati.
 */
class IssueTickets
{
    public function __construct(private TicketSigner $signer) {}

    /**
     * @return Collection<int, Ticket>
     */
    public function handle(Booking $booking): Collection
    {
        $sudahPunya = $booking->tickets()->pluck('participant_id')->all();

        foreach ($booking->participants as $peserta) {
            if (in_array($peserta->id, $sudahPunya, true)) {
                continue;
            }

            $ticket = $booking->tickets()->create([
                'participant_id' => $peserta->id,
                'token' => $this->tokenUnik(),
                // Diisi sementara: tanda tangan menyertakan id tiket, jadi baru
                // bisa dihitung setelah barisnya ada.
                'signature' => '',
                'status' => TicketStatus::Issued,
            ]);

            $ticket->setRelation('booking', $booking);
            $ticket->update(['signature' => $this->signer->sign($ticket)]);
        }

        return $booking->tickets()->with('participant')->get();
    }

    /**
     * Token 32 karakter acak. Panjang ini yang membuat tiket tidak bisa ditebak
     * — QR hanya berisi token, jadi kekuatan tiket sepenuhnya ada di sini
     * (ditambah tanda tangan HMAC yang memakai APP_KEY).
     */
    private function tokenUnik(): string
    {
        do {
            $token = Str::random(32);
        } while (Ticket::where('token', $token)->exists());

        return $token;
    }
}
