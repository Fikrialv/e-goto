<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Menyimpan bukti bayar dan menandai booking siap diverifikasi.
 */
class StorePaymentProof
{
    public function handle(Booking $booking, UploadedFile $proof, ?int $amountDeclared = null): Payment
    {
        if (! in_array($booking->status, [BookingStatus::PendingPayment, BookingStatus::AwaitingVerification], true)) {
            throw ValidationException::withMessages([
                'proof' => 'Booking ini sudah tidak menunggu pembayaran.',
            ]);
        }

        $hash = hash_file('sha256', $proof->getRealPath());

        /*
         * Bukti yang sama dipakai di booking lain hampir selalu berarti salah
         * satu dari dua hal: customer salah unggah, atau seseorang memakai satu
         * bukti transfer untuk dua pesanan. Keduanya butuh mata manusia, jadi
         * baris ini cuma diberi tanda — tidak ditolak otomatis (PLAN.md §5.2).
         */
        $duplikat = Payment::query()
            ->where('proof_hash', $hash)
            ->where('booking_id', '!=', $booking->id)
            ->exists();

        $path = $proof->store(config('booking.proof_directory'), config('booking.proof_disk'));

        return DB::transaction(function () use ($booking, $hash, $path, $duplikat, $amountDeclared) {
            $sebelumnya = $booking->payments()->latest()->first();

            // Unggahan ulang (mis. setelah ditolak) menggantikan berkas lama —
            // menyimpan semuanya cuma menumpuk data pribadi tanpa dipakai.
            if ($sebelumnya?->proof_path) {
                Storage::disk(config('booking.proof_disk'))->delete($sebelumnya->proof_path);
                $sebelumnya->update(['proof_path' => null]);
            }

            $payment = $booking->payments()->create([
                'method' => 'qris',
                'amount_declared' => $amountDeclared ?? $booking->total_amount,
                'proof_path' => $path,
                'proof_hash' => $hash,
                'is_duplicate_flagged' => $duplikat,
                'status' => PaymentStatus::Pending,
            ]);

            $booking->update(['status' => BookingStatus::AwaitingVerification]);

            return $payment;
        });
    }
}
