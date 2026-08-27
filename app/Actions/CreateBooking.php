<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\IdType;
use App\Models\Booking;
use App\Models\BookingParticipant;
use App\Models\Trip;
use App\Models\TripSchedule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Membuat booking sekaligus menahan kuota.
 *
 * Semua langkah yang menyentuh uang dan kursi dikumpulkan di satu transaksi:
 * harga dihitung ulang dari database (input harga dari form tidak pernah
 * dipercaya), kursi dikunci dengan lockForUpdate supaya dua pemesan bersamaan
 * tidak menembus kuota, dan nominal unik dijamin tidak bentrok dengan booking
 * lain yang masih menunggu pembayaran.
 */
class CreateBooking
{
    /**
     * @param  array<int, array{full_name: string, phone?: string|null, id_number?: string|null, dob?: string|null, emergency_contact?: string|null}>  $participants
     *
     * @throws ValidationException
     */
    public function handle(
        User $user,
        TripSchedule $schedule,
        array $participants,
        ?string $notes = null,
        array $options = [],
        ?string $voucherCode = null,
    ): Booking {
        $paxCount = count($participants);

        return DB::transaction(function () use ($user, $schedule, $participants, $paxCount, $notes, $options, $voucherCode) {
            /*
             * Baris jadwal dikunci lebih dulu, dan angka kuota dibaca ULANG dari
             * baris terkunci itu — bukan dari model yang sudah dimuat controller.
             * Antara halaman dibuka dan tombol ditekan, orang lain bisa saja
             * sudah mengambil kursi terakhir.
             */
            $terkunci = TripSchedule::query()
                ->whereKey($schedule->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $sisa = max(0, $terkunci->quota - $terkunci->booked_count);

            if ($paxCount > $sisa) {
                throw ValidationException::withMessages([
                    'pax_count' => $sisa === 0
                        ? 'Kuota jadwal ini sudah habis.'
                        : "Sisa kuota tinggal {$sisa} kursi, tidak cukup untuk {$paxCount} peserta.",
                ]);
            }

            $hargaSatuan = $this->hargaPerPeserta($terkunci, $paxCount);
            $baris = $this->opsiTerpilih($terkunci->trip, $options, $paxCount);

            /*
             * Urutan ini penting: opsi tambahan masuk subtotal DULU, potongan
             * voucher dihitung dari subtotal itu, baru nominal unik ditempel
             * paling akhir (PLAN.md §5.1) supaya tetap jadi pembeda terakhir
             * yang dicocokkan admin dengan mutasi bank.
             */
            $subtotal = $hargaSatuan * $paxCount + array_sum(array_map(
                fn (array $item): int => $item['unit_price'] * $item['qty'],
                $baris
            ));

            $voucher = null;
            $potongan = 0;

            if (filled($voucherCode)) {
                $hasil = app(ApplyVoucher::class)->handle($voucherCode, $user, $terkunci->trip, $subtotal);
                $voucher = $hasil['voucher'];
                $potongan = $hasil['potongan'];
            }

            $uniqueCode = $this->nominalUnik($subtotal - $potongan);

            $booking = Booking::create([
                'code' => $this->kodeBooking(),
                'user_id' => $user->id,
                'trip_schedule_id' => $terkunci->id,
                'pax_count' => $paxCount,
                'subtotal' => $subtotal,
                'discount_amount' => $potongan,
                'unique_code' => $uniqueCode,
                'total_amount' => $subtotal - $potongan + $uniqueCode,
                'status' => BookingStatus::PendingPayment,
                'expires_at' => Carbon::now()->addMinutes((int) config('booking.expiry_minutes')),
                'notes' => $notes,
            ]);

            $idType = $terkunci->trip->category->id_requirement;

            foreach (array_values($participants) as $urutan => $peserta) {
                $this->simpanPeserta($booking, $peserta, $idType, isLeader: $urutan === 0);
            }

            foreach ($baris as $item) {
                $booking->options()->create($item);
            }

            if ($voucher !== null) {
                // Pemakaian dicatat di dalam transaksi yang sama dengan
                // booking-nya: kalau salah satunya gagal, kuota voucher
                // tidak boleh ikut terpakai.
                $voucher->usages()->create([
                    'booking_id' => $booking->id,
                    'user_id' => $user->id,
                    'amount_cut' => $potongan,
                ]);

                $voucher->increment('used_count');
            }

            /*
             * Kuota ditahan sejak sekarang, bukan sejak dibayar. Kalau tidak
             * dibayar sampai expires_at, `bookings:expire` yang melepasnya.
             */
            $terkunci->increment('booked_count', $paxCount);

            return $booking;
        });
    }

    /**
     * @param  array{full_name: string, phone?: string|null, id_number?: string|null, dob?: string|null, emergency_contact?: string|null}  $peserta
     */
    private function simpanPeserta(Booking $booking, array $peserta, IdType $idType, bool $isLeader): void
    {
        $idNumber = $idType === IdType::None
            ? null
            : trim((string) ($peserta['id_number'] ?? ''));

        $booking->participants()->create([
            'is_leader' => $isLeader,
            'full_name' => $peserta['full_name'],
            'phone' => $peserta['phone'] ?? null,
            'id_type' => $idType,
            // Cast `encrypted` di model yang mengenkripsi; hash dihitung lewat
            // helper model supaya algoritmanya cuma ditulis di satu tempat.
            'id_number' => $idNumber ?: null,
            'id_number_hash' => $idNumber ? BookingParticipant::hashFor($idNumber) : null,
            'dob' => $peserta['dob'] ?? null,
            'emergency_contact' => $peserta['emergency_contact'] ?? null,
        ]);
    }

    /**
     * Opsi tambahan yang dipilih customer, divalidasi ulang dari database.
     *
     * Harga diambil dari kolom `extra_price` saat ini lalu dibekukan di
     * `booking_options.unit_price` — kalau mitra menaikkan harga besok, total
     * yang sudah disepakati tidak ikut berubah. Jumlahnya dibatasi jumlah
     * peserta: opsi dijual per orang, bukan per rombongan.
     *
     * @param  array<int, int>  $options  trip_option_id => qty
     * @return array<int, array{trip_option_id: int, qty: int, unit_price: int}>
     *
     * @throws ValidationException
     */
    private function opsiTerpilih(Trip $trip, array $options, int $paxCount): array
    {
        $dipilih = array_filter($options, fn ($qty): bool => (int) $qty > 0);

        if ($dipilih === []) {
            return [];
        }

        $tersedia = $trip->options()->where('is_active', true)->get()->keyBy('id');
        $baris = [];

        foreach ($dipilih as $optionId => $qty) {
            $opsi = $tersedia->get((int) $optionId);

            if ($opsi === null) {
                throw ValidationException::withMessages([
                    'options' => 'Ada opsi tambahan yang sudah tidak tersedia. Muat ulang halaman lalu coba lagi.',
                ]);
            }

            if ((int) $qty > $paxCount) {
                throw ValidationException::withMessages([
                    'options' => 'Jumlah opsi "'.$opsi->name.'" tidak boleh melebihi jumlah peserta.',
                ]);
            }

            $baris[] = [
                'trip_option_id' => $opsi->id,
                'qty' => (int) $qty,
                'unit_price' => (int) $opsi->extra_price,
            ];
        }

        return $baris;
    }

    /**
     * Harga bertingkat: baris yang cocok dengan jumlah peserta. Kalau beberapa
     * baris cocok, yang termurah dipakai — tingkatan harga memang dibuat untuk
     * menguntungkan rombongan besar, jadi salah pilih ke atas merugikan customer.
     *
     * @throws ValidationException
     */
    private function hargaPerPeserta(TripSchedule $schedule, int $paxCount): int
    {
        $harga = $schedule->prices()
            ->where('min_pax', '<=', $paxCount)
            ->where(fn ($query) => $query->whereNull('max_pax')->orWhere('max_pax', '>=', $paxCount))
            ->orderBy('price')
            ->value('price');

        if ($harga === null) {
            throw ValidationException::withMessages([
                'pax_count' => "Tidak ada paket harga untuk {$paxCount} peserta pada jadwal ini.",
            ]);
        }

        return (int) $harga;
    }

    /**
     * Nominal unik (PLAN.md §5.1): 3 digit yang ditambahkan ke subtotal supaya
     * admin bisa mencocokkan mutasi bank. Yang harus dijamin unik adalah
     * nominal akhir di antara booking yang masih ditunggu pembayarannya —
     * dua booking dengan subtotal berbeda boleh punya 3 digit yang sama.
     */
    private function nominalUnik(int $subtotal): int
    {
        $terpakai = Booking::query()
            ->where('subtotal', $subtotal)
            ->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::AwaitingVerification])
            ->pluck('unique_code')
            ->map(fn ($kode) => (int) $kode)
            ->all();

        $percobaan = (int) config('booking.unique_code_attempts');

        for ($i = 0; $i < $percobaan; $i++) {
            $kode = random_int(1, 999);

            if (! in_array($kode, $terpakai, true)) {
                return $kode;
            }
        }

        // 3 digit padat — naik ke 4 digit, ruangnya 10x lebih lega.
        for ($i = 0; $i < $percobaan; $i++) {
            $kode = random_int(1000, 9999);

            if (! in_array($kode, $terpakai, true)) {
                return $kode;
            }
        }

        throw ValidationException::withMessages([
            'pax_count' => 'Sistem sedang sibuk memproses pemesanan serupa. Coba lagi sebentar lagi.',
        ]);
    }

    /**
     * Kode booking ditulis manusia di catatan transfer, jadi hurufnya dibatasi
     * ke himpunan yang tidak mudah tertukar saat dibaca (tanpa 0/O, 1/I).
     */
    private function kodeBooking(): string
    {
        do {
            $acak = strtr(Str::upper(Str::random(4)), ['0' => '2', 'O' => 'Q', '1' => '7', 'I' => 'J', 'L' => 'K']);
            $kode = 'EGT-'.Carbon::now()->format('ymd').'-'.$acak;
        } while (Booking::where('code', $kode)->exists());

        return $kode;
    }
}
