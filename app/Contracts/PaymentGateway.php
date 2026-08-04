<?php

namespace App\Contracts;

use App\Models\Booking;
use App\Models\Payment;

/**
 * Batas antara alur booking dan cara pembayaran diproses.
 *
 * V1 diisi ManualQrisGateway (QRIS statis + nominal unik, verifikasi manual
 * admin — dibuat di D5). Saat nanti pindah ke Midtrans, yang berubah cuma
 * binding di service provider, alur booking tidak ikut dibongkar.
 */
interface PaymentGateway
{
    /**
     * Instruksi bayar yang ditampilkan ke customer (nominal unik, kode booking,
     * cara transfer). Bentuk konkretnya ditentukan implementasi di D5.
     */
    public function createCharge(Booking $booking): mixed;

    /**
     * Apakah pembayaran ini sah. V1: hasil keputusan admin.
     * Gateway otomatis nanti: hasil verifikasi webhook/API.
     */
    public function verify(Payment $payment): bool;
}
