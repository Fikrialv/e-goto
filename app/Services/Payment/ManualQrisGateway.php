<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Pembayaran V1: QRIS statis + nominal unik, diverifikasi manusia.
 *
 * Tidak ada panggilan API ke mana pun — keabsahan pembayaran ditentukan admin
 * setelah mencocokkan bukti transfer dengan nominal unik. Saat nanti pindah ke
 * Midtrans, cukup ganti binding PaymentGateway; alur booking tidak dibongkar.
 */
class ManualQrisGateway implements PaymentGateway
{
    public function __construct(private readonly QrisDynamicPayload $payload) {}

    public function createCharge(Booking $booking): PaymentInstruction
    {
        return new PaymentInstruction(
            bookingCode: $booking->code,
            totalAmount: $booking->total_amount,
            uniqueCode: $booking->unique_code,
            qrisImagePath: (string) config('booking.qris_image_path'),
            merchantName: (string) config('booking.qris_merchant_name'),
            expiresAt: $booking->expires_at,
            qrisPayload: $this->payloadBernominal($booking),
        );
    }

    /**
     * Nominal diambil dari kolom booking, tidak pernah dari request — halaman
     * bayar tidak menerima parameter nominal, jadi tidak ada yang bisa digeser
     * lewat URL.
     *
     * Payload dihitung saat render, bukan disimpan: ia sepenuhnya turunan dari
     * `total_amount` yang sudah beku sejak booking dibuat, dan kolom tambahan
     * cuma menambah satu tempat yang bisa basi.
     */
    private function payloadBernominal(Booking $booking): ?string
    {
        $statis = config('booking.qris_static_payload');

        if (blank($statis)) {
            return null;
        }

        try {
            return $this->payload->untukNominal((string) $statis, $booking->total_amount);
        } catch (InvalidArgumentException $e) {
            // QR rusak lebih berbahaya daripada tidak ada QR dinamis sama sekali:
            // kegagalannya baru terlihat di depan kasir. Jatuh ke gambar statis.
            Log::warning('QRIS_STATIC_PAYLOAD tidak bisa dipakai.', [
                'booking' => $booking->code,
                'alasan' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * V1 tidak bisa memastikan apa pun sendiri: sumber kebenarannya adalah
     * keputusan admin yang sudah tercatat di baris pembayaran.
     */
    public function verify(Payment $payment): bool
    {
        return $payment->status === PaymentStatus::Verified;
    }
}
