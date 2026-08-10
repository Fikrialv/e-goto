<?php

use App\Enums\IdType;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Penjaga N+1 (D7).
 *
 * Halaman di bawah menampilkan data dari beberapa tabel sekaligus, dan
 * jumlahnya bertambah seiring data bertambah — persis pola yang membuat halaman
 * ikut melambat diam-diam saat produksi terisi. Batas query di sini bukan angka
 * keramat; gunanya adalah gagal keras kalau suatu saat ada eager load yang
 * terhapus.
 */
function hitungQuery(callable $aksi): int
{
    $jumlah = 0;

    DB::listen(function () use (&$jumlah) {
        $jumlah++;
    });

    $aksi();

    return $jumlah;
}

it('menampilkan Booking Saya tanpa query beranak per baris', function () {
    Storage::fake('local');
    $user = User::factory()->customer()->create();

    // Lima booking di lima jadwal berbeda: kalau relasinya tidak di-eager load,
    // tiap baris menambah query trip + kategori sendiri.
    foreach (range(1, 5) as $i) {
        $schedule = jadwalUntukBooking(IdType::None, quota: 10, harga: 400_000);
        kirimBooking($schedule, [['full_name' => "Peserta {$i}"]], $user);
    }

    expect(Booking::where('user_id', $user->id)->count())->toBe(5);

    $jumlah = hitungQuery(function () use ($user) {
        $this->actingAs($user)->get('/booking-saya')->assertOk();
    });

    expect($jumlah)->toBeLessThan(15);
});

it('menampilkan homepage dengan jumlah query yang tidak ikut tumbuh bersama data', function () {
    Cache::flush();

    foreach (range(1, 6) as $i) {
        tripPublished(harga: 300_000 + ($i * 10_000));
    }

    $jumlah = hitungQuery(function () {
        $this->get('/')->assertOk();
    });

    expect($jumlah)->toBeLessThan(20);
});
