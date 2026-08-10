<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;

/**
 * Pembayaran V1: QRIS statis + nominal unik, diverifikasi manusia.
 *
 * Tidak ada panggilan API ke mana pun — keabsahan pembayaran ditentukan admin
 * setelah mencocokkan bukti transfer dengan nominal unik. Saat nanti pindah ke
 * Midtrans, cukup ganti binding PaymentGateway; alur booking tidak dibongkar.
 */
class ManualQrisGateway implements PaymentGateway
{
    public function createCharge(Booking $booking): PaymentInstruction
    {
        return new PaymentInstruction(
            bookingCode: $booking->code,
            totalAmount: $booking->total_amount,
            uniqueCode: $booking->unique_code,
            qrisImagePath: (string) config('booking.qris_image_path'),
            merchantName: (string) config('booking.qris_merchant_name'),
            expiresAt: $booking->expires_at,
        );
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
