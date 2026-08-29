<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\RefundStatus;
use App\Enums\RefundType;
use App\Models\Booking;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Siklus hidup satu pengajuan refund: diajukan customer, diputuskan admin,
 * lalu ditandai selesai setelah uangnya benar-benar dikirim.
 *
 * Dikumpulkan di satu kelas karena tiga langkahnya berbagi penjaga yang sama
 * dan sebagian mengubah dua baris sekaligus (pengajuan + booking) — sama
 * dengan alasan VerifyPayment menyatukan approve dan reject.
 *
 * Mitra tidak punya jalan masuk ke kelas ini sama sekali. Uang masuk ke
 * rekening E-GOTO, jadi yang mengembalikannya juga E-GOTO.
 */
class ProcessRefundRequest
{
    /**
     * @throws ValidationException
     */
    public function ajukan(Booking $booking, RefundType $type, ?string $catatan = null): RefundRequest
    {
        return DB::transaction(function () use ($booking, $type, $catatan) {
            /*
             * Kunci barisnya dulu. Tanpa ini, dua kiriman form yang beriringan
             * (tombol diklik dua kali, atau satu tab yang di-refresh) sama-sama
             * lolos pemeriksaan "belum ada pengajuan berjalan" dan membuat dua
             * pengajuan untuk booking yang sama — yang kemudian bisa diproses
             * dua admin berbeda.
             */
            $terkunci = Booking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();

            if (! $terkunci->bolehAjukanRefund()) {
                throw ValidationException::withMessages([
                    'type' => 'Booking ini tidak bisa diajukan refund sekarang.',
                ]);
            }

            return RefundRequest::create([
                'booking_id' => $terkunci->getKey(),
                'type' => $type,
                'status' => RefundStatus::Diajukan,
                'customer_note' => $catatan,
            ]);
        });
    }

    /**
     * @throws ValidationException
     */
    public function setujui(RefundRequest $request, User $admin, ?string $catatan = null): RefundRequest
    {
        return DB::transaction(function () use ($request, $admin, $catatan) {
            $terkunci = $this->kunci($request);

            if ($terkunci->status !== RefundStatus::Diajukan) {
                throw ValidationException::withMessages([
                    'status' => 'Pengajuan ini sudah diputuskan sebelumnya.',
                ]);
            }

            $terkunci->update([
                'status' => RefundStatus::Disetujui,
                'admin_note' => $catatan,
                'processed_by' => $admin->getKey(),
                'processed_at' => now(),
            ]);

            /*
             * Kursi dilepas saat DISETUJUI, bukan saat uang terkirim: begitu
             * keputusan diambil, peserta ini sudah pasti tidak berangkat, dan
             * menahan kursinya sampai transfer selesai berarti menahan kursi
             * yang bisa dijual ke orang lain.
             *
             * Pengecualian: opsi "pindah trip" dan "waitlist" TIDAK melepas
             * kursi lewat jalur ini — pemindahannya dikerjakan admin manual
             * (GUIDE.md: selisih harga dibicarakan case-by-case), dan booking
             * lamanya baru dibatalkan setelah penggantinya benar-benar ada.
             */
            if ($terkunci->type === RefundType::Refund100) {
                $terkunci->booking->update(['status' => BookingStatus::Cancelled]);
            }

            return $terkunci->refresh();
        });
    }

    /**
     * @throws ValidationException
     */
    public function tolak(RefundRequest $request, User $admin, string $alasan): RefundRequest
    {
        // Alasan wajib — penjaga yang sama dengan penolakan bukti bayar (D5)
        // dan penolakan pengajuan trip mitra (D9). Tanpa alasan, customer tidak
        // punya satu pun petunjuk untuk memperbaiki pengajuannya.
        if (trim($alasan) === '') {
            throw ValidationException::withMessages([
                'admin_note' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($request, $admin, $alasan) {
            $terkunci = $this->kunci($request);

            if ($terkunci->status !== RefundStatus::Diajukan) {
                throw ValidationException::withMessages([
                    'status' => 'Pengajuan ini sudah diputuskan sebelumnya.',
                ]);
            }

            $terkunci->update([
                'status' => RefundStatus::Ditolak,
                'admin_note' => $alasan,
                'processed_by' => $admin->getKey(),
                'processed_at' => now(),
            ]);

            return $terkunci->refresh();
        });
    }

    /**
     * Uangnya sudah benar-benar dikirim / trip pengganti sudah dibuatkan.
     *
     * @throws ValidationException
     */
    public function tandaiSelesai(RefundRequest $request, User $admin): RefundRequest
    {
        return DB::transaction(function () use ($request, $admin) {
            $terkunci = $this->kunci($request);

            if ($terkunci->status !== RefundStatus::Disetujui) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pengajuan yang sudah disetujui yang bisa ditandai selesai.',
                ]);
            }

            $terkunci->update([
                'status' => RefundStatus::Selesai,
                'processed_by' => $admin->getKey(),
                'processed_at' => now(),
            ]);

            return $terkunci->refresh();
        });
    }

    private function kunci(RefundRequest $request): RefundRequest
    {
        return RefundRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();
    }
}
