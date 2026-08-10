<?php

namespace App\Actions;

use App\Contracts\TicketSigner;
use App\Enums\BookingStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Check-in peserta di lapangan.
 *
 * Ancaman yang ditangani di sini: tiket palsu (tanda tangan HMAC tidak cocok),
 * tiket dipakai dua kali (status dikunci baris di dalam transaksi), dan vendor
 * yang mencoba men-check-in peserta trip milik orang lain.
 */
class CheckInTicket
{
    public function __construct(private TicketSigner $signer) {}

    /**
     * @throws ValidationException
     */
    public function handle(string $token, User $petugas): Ticket
    {
        $token = trim($token);

        $ticket = Ticket::query()
            ->with(['booking.schedule.trip', 'participant'])
            ->where('token', $token)
            ->first();

        /*
         * Pesan sengaja sama untuk "token tidak ada" dan "tanda tangan salah":
         * membedakan keduanya memberi tahu penyerang bahwa tokennya benar dan
         * yang salah cuma tanda tangannya.
         */
        if (! $ticket || ! $this->signer->verify($ticket, $ticket->signature)) {
            throw ValidationException::withMessages([
                'token' => 'Tiket tidak valid. Periksa kembali kode atau minta peserta menunjukkan e-tiket resminya.',
            ]);
        }

        $this->pastikanBerwenang($ticket, $petugas);

        if ($ticket->booking->status !== BookingStatus::Confirmed) {
            throw ValidationException::withMessages([
                'token' => 'Booking tiket ini belum terkonfirmasi ('.$ticket->booking->status->value.').',
            ]);
        }

        return DB::transaction(function () use ($ticket, $petugas) {
            $terkunci = Ticket::query()->whereKey($ticket->getKey())->lockForUpdate()->firstOrFail();

            if ($terkunci->status === TicketStatus::Used) {
                throw ValidationException::withMessages([
                    'token' => 'Tiket ini sudah dipakai '.$terkunci->checked_in_at?->translatedFormat('j F Y, H:i').'.',
                ]);
            }

            if ($terkunci->status !== TicketStatus::Issued) {
                throw ValidationException::withMessages([
                    'token' => 'Tiket ini dibatalkan dan tidak bisa dipakai.',
                ]);
            }

            $terkunci->update([
                'status' => TicketStatus::Used,
                'checked_in_at' => Carbon::now(),
                'checked_in_by' => $petugas->id,
            ]);

            return $terkunci->load(['booking.schedule.trip', 'participant']);
        });
    }

    /**
     * Admin boleh men-check-in apa saja. Vendor hanya trip miliknya — dan trip
     * milik E-GOTO (`vendor_id` null) tidak boleh disentuh vendor mana pun.
     *
     * @throws ValidationException
     */
    private function pastikanBerwenang(Ticket $ticket, User $petugas): void
    {
        if ($petugas->role === UserRole::Admin) {
            return;
        }

        $vendorId = $ticket->booking->schedule->trip->vendor_id;

        if ($petugas->role !== UserRole::Vendor || $vendorId === null || $vendorId !== $petugas->id) {
            throw ValidationException::withMessages([
                'token' => 'Tiket ini bukan untuk trip Anda.',
            ]);
        }
    }
}
