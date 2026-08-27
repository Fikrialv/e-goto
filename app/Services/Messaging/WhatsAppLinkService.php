<?php

namespace App\Services\Messaging;

use App\Contracts\MessagingService;
use App\Models\Booking;
use App\Models\Trip;
use Illuminate\Support\Str;

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

        return $this->tautan($pesan);
    }

    /**
     * Pengingat H-1 ke nomor customer. Berbeda dari dua method lain: tujuannya
     * nomor pemesan, bukan admin — admin yang menekan tombolnya dari panel.
     *
     * Isi pesan sengaja dibatasi ke hal yang dibutuhkan peserta besok pagi.
     * Data peserta selain nama pemesan tidak ikut, NIK/paspor apalagi.
     */
    public function remindDayBefore(Booking $booking): string
    {
        $schedule = $booking->schedule;
        $trip = $schedule->trip;

        $baris = [
            'Halo! Pengingat keberangkatan besok dari E-GOTO.',
            '',
            'Trip: '.$trip->title,
            'Kode booking: '.$booking->code,
            'Tanggal: '.$schedule->start_date->translatedFormat('l, j F Y'),
            'Jumlah peserta: '.$booking->pax_count.' orang',
        ];

        if (filled($trip->meeting_point)) {
            $baris[] = 'Titik kumpul: '.$trip->meeting_point;
        }

        if (filled($trip->itinerary)) {
            $baris[] = '';
            $baris[] = 'Rencana singkat: '.Str::limit(preg_replace('/\s+/', ' ', (string) $trip->itinerary), 200);
        }

        $checklist = $trip->category?->gear_checklist ?? [];

        if (filled($checklist)) {
            $baris[] = '';
            $baris[] = 'Jangan lupa bawa:';

            foreach ($checklist as $barang) {
                $baris[] = '- '.$barang;
            }
        }

        $baris[] = '';
        $baris[] = 'Sampai ketemu besok!';

        return $this->tautan(implode("\n", $baris), $this->nomorTujuan($booking));
    }

    public function requestPrivateTrip(Trip $trip): string
    {
        $pesan = sprintf(
            "Halo Admin E-GOTO, saya mau menanyakan private trip untuk rombongan lebih dari %d orang.\n\nTrip: %s\n\nMohon informasinya, terima kasih.",
            (int) config('booking.max_pax_per_booking'),
            $trip->title,
        );

        return $this->tautan($pesan);
    }

    public function requestPrivateTripForm(array $permintaan): string
    {
        $baris = [
            'Halo Admin E-GOTO, saya mau menanyakan private trip.',
            '',
            'Nama: '.$permintaan['contact_name'],
            'Tujuan: '.$permintaan['destination'],
        ];

        if (filled($permintaan['depart_on'] ?? null)) {
            $baris[] = 'Perkiraan berangkat: '.$permintaan['depart_on'];
        }

        if (filled($permintaan['pax'] ?? null)) {
            $baris[] = 'Jumlah peserta: '.$permintaan['pax'].' orang';
        }

        if (filled($permintaan['notes'] ?? null)) {
            $baris[] = '';
            $baris[] = 'Catatan: '.$permintaan['notes'];
        }

        $baris[] = '';
        $baris[] = 'Mohon informasinya, terima kasih.';

        return $this->tautan(implode("\n", $baris));
    }

    public function generalEnquiry(): string
    {
        return $this->tautan('Halo Admin E-GOTO, saya mau bertanya.');
    }

    private function tautan(string $pesan, ?string $nomor = null): string
    {
        return 'https://wa.me/'.($nomor ?? config('booking.admin_whatsapp')).'?text='.rawurlencode($pesan);
    }

    /**
     * Nomor pemesan dalam format internasional tanpa plus — bentuk yang
     * diterima wa.me. Nomor ketua rombongan dipakai lebih dulu karena dialah
     * yang mengisi kontak saat memesan; nomor akun jadi cadangan.
     */
    private function nomorTujuan(Booking $booking): string
    {
        $mentah = $booking->participants->firstWhere('is_leader', true)?->phone
            ?? $booking->user?->phone
            ?? '';

        $angka = preg_replace('/\D/', '', $mentah);

        if ($angka === '' || $angka === null) {
            return (string) config('booking.admin_whatsapp');
        }

        return str_starts_with($angka, '0')
            ? '62'.substr($angka, 1)
            : $angka;
    }
}
