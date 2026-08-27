<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\TripSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Melepas kursi yang ditahan booking yang tidak kunjung dibayar.
 *
 * Dijalankan lewat `schedule:run` (cron), BUKAN queue worker: hosting target
 * adalah shared hosting tanpa proses latar persisten, dan ini satu-satunya
 * pekerjaan periodik di V1 (CLAUDE.md §1, PLAN.md §12b).
 */
class ExpireBookings extends Command
{
    protected $signature = 'bookings:expire';

    protected $description = 'Kedaluwarsakan booking yang belum dibayar dan kembalikan kuotanya';

    public function handle(): int
    {
        $kedaluwarsa = Booking::query()
            ->where('status', BookingStatus::PendingPayment)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $jumlah = 0;

        foreach ($kedaluwarsa as $booking) {
            /*
             * Satu transaksi per booking, bukan satu untuk semuanya: kalau ada
             * satu baris bermasalah, sisanya tetap terlepas — dan transaksi
             * panjang yang mengunci banyak baris jadwal sekaligus justru
             * memblokir pemesanan yang sedang berjalan.
             */
            $berhasil = DB::transaction(function () use ($booking) {
                /*
                 * Urutan kunci: JADWAL DULU, baru booking — sama persis dengan
                 * CreateBooking. Dua alur yang menyentuh pasangan baris yang
                 * sama tapi menguncinya dengan urutan terbalik adalah resep
                 * deadlock: yang satu memegang jadwal sambil menunggu booking,
                 * yang lain sebaliknya. Jangan tukar urutan ini.
                 */
                $jadwal = TripSchedule::query()
                    ->whereKey($booking->trip_schedule_id)
                    ->lockForUpdate()
                    ->first();

                $terkunci = Booking::query()
                    ->whereKey($booking->getKey())
                    ->lockForUpdate()
                    ->first();

                // Dicek ulang di dalam kunci: booking bisa saja baru dibayar
                // beberapa detik lalu, antara query di atas dan baris ini.
                if (! $terkunci || $terkunci->status !== BookingStatus::PendingPayment) {
                    return false;
                }

                $terkunci->update(['status' => BookingStatus::Expired]);

                if ($jadwal) {
                    // max() menjaga dari nilai negatif kalau ada koreksi manual
                    // di database yang membuat hitungan tidak lagi cocok.
                    $jadwal->update([
                        'booked_count' => max(0, $jadwal->booked_count - $terkunci->pax_count),
                    ]);
                }

                return true;
            });

            if ($berhasil) {
                $jumlah++;
            }
        }

        $this->info("{$jumlah} booking dikedaluwarsakan, kuotanya dikembalikan.");

        return self::SUCCESS;
    }
}
