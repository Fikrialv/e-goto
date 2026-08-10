<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Angka yang menentukan apakah admin perlu bertindak hari ini.
 *
 * Hanya tiga: pembayaran menunggu (satu-satunya antrean kerja yang mengunci
 * customer), booking yang menahan kuota, dan bukti bertanda duplikat. Dashboard
 * analitik penuh sengaja tidak dibuat di V1 — itu backlog.
 */
class PendingPaymentsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $menunggu = Payment::where('status', PaymentStatus::Pending)->count();
        $duplikat = Payment::where('status', PaymentStatus::Pending)->where('is_duplicate_flagged', true)->count();

        $menahanKuota = Booking::whereIn('status', [
            BookingStatus::PendingPayment,
            BookingStatus::AwaitingVerification,
        ])->count();

        return [
            Stat::make('Pembayaran menunggu verifikasi', $menunggu)
                ->description($menunggu > 0 ? 'Customer sedang menunggu keputusan Anda' : 'Antrean bersih')
                ->color($menunggu > 0 ? 'warning' : 'success')
                ->url(PaymentResource::getUrl()),

            Stat::make('Bukti ditandai duplikat', $duplikat)
                ->description($duplikat > 0 ? 'Perlu dicocokkan dengan mutasi rekening' : 'Tidak ada')
                ->color($duplikat > 0 ? 'danger' : 'gray'),

            Stat::make('Booking menahan kuota', $menahanKuota)
                ->description('Belum lunas, kursinya masih terpakai')
                ->color('gray'),
        ];
    }
}
