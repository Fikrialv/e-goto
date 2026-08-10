<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Keputusan admin atas satu bukti pembayaran.
 *
 * Approve dan reject dikumpulkan di satu kelas karena keduanya mengubah dua
 * baris sekaligus (payment + booking) dan harus atomik: booking yang sudah
 * `confirmed` sementara pembayarannya masih `pending` adalah keadaan yang
 * membingungkan admin maupun customer.
 */
class VerifyPayment
{
    public function __construct(private IssueTickets $issueTickets) {}

    public function approve(Payment $payment, User $admin): Payment
    {
        return DB::transaction(function () use ($payment, $admin) {
            $terkunci = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($terkunci->status !== PaymentStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Pembayaran ini sudah diputuskan sebelumnya.',
                ]);
            }

            $terkunci->update([
                'status' => PaymentStatus::Verified,
                'verified_by' => $admin->id,
                'verified_at' => Carbon::now(),
                'reject_reason' => null,
            ]);

            $booking = $terkunci->booking()->lockForUpdate()->firstOrFail();
            $booking->update([
                'status' => BookingStatus::Confirmed,
                'expires_at' => null,
            ]);

            // Tiket terbit langsung dalam request yang sama — tidak ada queue di
            // V1, dan customer memang menunggu tiketnya muncul saat itu juga.
            $this->issueTickets->handle($booking);

            return $terkunci->refresh();
        });
    }

    public function reject(Payment $payment, User $admin, string $reason): Payment
    {
        $reason = trim($reason);

        /*
         * Alasan wajib, dan dijaga di sini — bukan cuma di form. Alasan inilah
         * satu-satunya yang dibaca customer untuk tahu apa yang harus diperbaiki
         * saat mengunggah ulang; penolakan tanpa alasan memindahkan beban ke
         * customer service.
         */
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reject_reason' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($payment, $admin, $reason) {
            $terkunci = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($terkunci->status !== PaymentStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Pembayaran ini sudah diputuskan sebelumnya.',
                ]);
            }

            $terkunci->update([
                'status' => PaymentStatus::Rejected,
                'verified_by' => $admin->id,
                'verified_at' => Carbon::now(),
                'reject_reason' => $reason,
            ]);

            /*
             * Booking dikembalikan ke `pending_payment` supaya customer bisa
             * unggah ulang, dan batas waktunya disegarkan: kalau tidak, bukti
             * yang ditolak menjelang jam kedua langsung kedaluwarsa sebelum
             * customer sempat memperbaiki.
             */
            $booking = $terkunci->booking()->lockForUpdate()->firstOrFail();
            $booking->update([
                'status' => BookingStatus::PendingPayment,
                'expires_at' => Carbon::now()->addMinutes((int) config('booking.expiry_minutes')),
            ]);

            return $terkunci->refresh();
        });
    }
}
