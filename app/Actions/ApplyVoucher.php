<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\VoucherScope;
use App\Models\Trip;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Validation\ValidationException;

/**
 * Validasi voucher di satu tempat (D10).
 *
 * Semua cabang penolakan dikumpulkan di sini supaya checkout, halaman detail,
 * dan layar admin tidak pernah punya versi aturan yang berbeda. Yang dikirim
 * form cuma kode; nilai potongannya dihitung ulang dari database — angka
 * potongan dari sisi klien tidak pernah dipercaya.
 */
class ApplyVoucher
{
    /**
     * @return array{voucher: Voucher, potongan: int}
     *
     * @throws ValidationException
     */
    public function handle(string $kode, User $user, Trip $trip, int $subtotal): array
    {
        $voucher = Voucher::whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($kode))])->first();

        $this->tolak($voucher === null, 'Kode voucher tidak ditemukan.');
        $this->tolak(! $voucher->is_active, 'Voucher ini sedang tidak aktif.');

        $this->tolak(
            $voucher->valid_from !== null && $voucher->valid_from->isFuture(),
            'Voucher ini belum berlaku. Mulai berlaku '.$voucher->valid_from?->translatedFormat('j F Y, H:i').'.'
        );

        $this->tolak(
            $voucher->valid_until !== null && $voucher->valid_until->isPast(),
            'Voucher ini sudah kedaluwarsa.'
        );

        $this->tolak(
            $voucher->quota !== null && $voucher->used_count >= $voucher->quota,
            'Kuota voucher ini sudah habis dipakai.'
        );

        $this->tolak(
            $voucher->min_spend !== null && $subtotal < $voucher->min_spend,
            'Voucher ini berlaku untuk pemesanan minimal Rp'.number_format($voucher->min_spend, 0, ',', '.').'.'
        );

        $this->tolak(! $this->cakupanCocok($voucher, $trip), 'Voucher ini tidak berlaku untuk trip yang Anda pilih.');

        // Dobel pakai per user. Booking yang kedaluwarsa/dibatalkan tidak ikut
        // dihitung — kesempatan pakainya memang tidak jadi terpakai.
        $sudahDipakai = $voucher->usages()
            ->where('user_id', $user->id)
            ->whereHas('booking', fn ($query) => $query->whereNotIn('status', [
                BookingStatus::Expired,
                BookingStatus::Cancelled,
                BookingStatus::Rejected,
            ]))
            ->exists();

        $this->tolak($sudahDipakai, 'Anda sudah pernah memakai voucher ini.');

        return ['voucher' => $voucher, 'potongan' => $voucher->potonganUntuk($subtotal)];
    }

    private function cakupanCocok(Voucher $voucher, Trip $trip): bool
    {
        return match ($voucher->scope) {
            VoucherScope::Global => true,
            VoucherScope::Category => (int) $voucher->scope_id === (int) $trip->category_id,
            VoucherScope::Trip => (int) $voucher->scope_id === (int) $trip->id,
        };
    }

    /**
     * @throws ValidationException
     */
    private function tolak(bool $gagal, string $pesan): void
    {
        if ($gagal) {
            throw ValidationException::withMessages(['voucher_code' => $pesan]);
        }
    }
}
