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
