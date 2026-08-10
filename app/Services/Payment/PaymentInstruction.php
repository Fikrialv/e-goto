<?php

namespace App\Services\Payment;

use Illuminate\Support\Carbon;

/**
 * Instruksi bayar yang ditampilkan ke customer.
 *
 * Dibuat sebagai objek (bukan array bebas) supaya halaman pembayaran punya
 * kontrak jelas: saat gateway otomatis menggantikan QRIS manual nanti, yang
 * berubah cuma pengisi objek ini, bukan view-nya.
 */
class PaymentInstruction
{
    public function __construct(
        public readonly string $bookingCode,
        public readonly int $totalAmount,
        public readonly int $uniqueCode,
        public readonly string $qrisImagePath,
        public readonly string $merchantName,
        public readonly ?Carbon $expiresAt,
    ) {}
}
