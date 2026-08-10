<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PendingPayment = 'pending_payment';
    case AwaitingVerification = 'awaiting_verification';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    /**
     * Label bahasa Indonesia untuk ditampilkan ke customer. Nilai mentah enum
     * ("awaiting_verification") tidak pernah tampil di layar.
     */
    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Menunggu pembayaran',
            self::AwaitingVerification => 'Menunggu verifikasi',
            self::Confirmed => 'Terkonfirmasi',
            self::Rejected => 'Ditolak',
            self::Expired => 'Kedaluwarsa',
            self::Cancelled => 'Dibatalkan',
            self::Completed => 'Selesai',
        };
    }

    /**
     * Status yang masih menahan kuota trip. Dipakai saat melepas kuota
     * (bookings:expire, D4) dan saat cek bentrok nominal unik (D4).
     */
    public function holdsQuota(): bool
    {
        return match ($this) {
            self::PendingPayment, self::AwaitingVerification, self::Confirmed, self::Completed => true,
            self::Rejected, self::Expired, self::Cancelled => false,
        };
    }
}
