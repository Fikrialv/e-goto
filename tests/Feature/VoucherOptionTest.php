<?php

use App\Enums\BookingStatus;
use App\Enums\IdType;
use App\Enums\VoucherScope;
use App\Enums\VoucherType;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Trip;
use App\Models\TripOption;
use App\Models\User;
use App\Models\Voucher;

/**
 * Voucher & opsi tambahan (D10).
 *
 * Yang dijaga: potongan dan harga opsi selalu dihitung ulang di server, urutan
 * hitungnya tetap (opsi masuk subtotal → voucher memotong → nominal unik
 * ditempel terakhir), dan tiap cabang penolakan voucher benar-benar menolak.
 */
function jadwalDenganOpsi(int $harga = 500_000): array
{
    $schedule = jadwalUntukBooking(IdType::None, quota: 20, harga: $harga);
    $opsi = TripOption::factory()->for($schedule->trip)->create(['extra_price' => 150_000, 'name' => 'Tubing']);

    return [$schedule, $opsi];
}

it('menambahkan harga opsi ke subtotal sebelum nominal unik', function () {
    [$schedule, $opsi] = jadwalDenganOpsi(500_000);
    $user = User::factory()->customer()->create();

    test()->actingAs($user)->post("/booking/{$schedule->id}", [
        'participants' => [['full_name' => 'Rina Hapsari'], ['full_name' => 'Bagas Prakoso']],
        'options' => [$opsi->id => 2],
    ])->assertRedirect();

    $booking = Booking::firstOrFail();

    expect($booking->subtotal)->toBe(2 * 500_000 + 2 * 150_000)
        ->and($booking->total_amount)->toBe($booking->subtotal + $booking->unique_code)
        ->and($booking->options()->count())->toBe(1)
        ->and($booking->options()->first()->unit_price)->toBe(150_000);
});

it('menolak jumlah opsi melebihi jumlah peserta', function () {
    [$schedule, $opsi] = jadwalDenganOpsi();
    $user = User::factory()->customer()->create();

    test()->actingAs($user)->post("/booking/{$schedule->id}", [
        'participants' => [['full_name' => 'Rina Hapsari']],
        'options' => [$opsi->id => 3],
    ])->assertSessionHasErrors('options');

    expect(Booking::count())->toBe(0);
});

it('memotong subtotal dengan voucher persen', function () {
    $schedule = jadwalUntukBooking(IdType::None, quota: 20, harga: 500_000);
    $voucher = Voucher::factory()->create(['code' => 'HEMAT10', 'value' => 10]);
    $user = User::factory()->customer()->create();

    test()->actingAs($user)->post("/booking/{$schedule->id}", [
        'participants' => [['full_name' => 'Rina Hapsari']],
        'voucher_code' => 'hemat10',
    ])->assertRedirect();

    $booking = Booking::firstOrFail();

    expect($booking->subtotal)->toBe(500_000)
        ->and($booking->discount_amount)->toBe(50_000)
        ->and($booking->total_amount)->toBe(450_000 + $booking->unique_code)
        ->and($voucher->fresh()->used_count)->toBe(1)
        ->and($booking->id)->toBe($voucher->usages()->first()->booking_id);
});

it('memotong dengan nominal tetap tanpa membuat total negatif', function () {
    $schedule = jadwalUntukBooking(IdType::None, quota: 20, harga: 100_000);
    Voucher::factory()->fixed(500_000)->create(['code' => 'GEDE']);
    $user = User::factory()->customer()->create();

    test()->actingAs($user)->post("/booking/{$schedule->id}", [
        'participants' => [['full_name' => 'Rina Hapsari']],
        'voucher_code' => 'GEDE',
    ])->assertRedirect();

    $booking = Booking::firstOrFail();

    expect($booking->discount_amount)->toBe(100_000)
        ->and($booking->total_amount)->toBe($booking->unique_code);
});

it('menolak voucher kedaluwarsa, nonaktif, kuota habis, dan minimal belanja', function (array $ubah, string $potongan) {
    $schedule = jadwalUntukBooking(IdType::None, quota: 20, harga: 300_000);
    Voucher::factory()->create(['code' => 'UJI'] + $ubah);
    $user = User::factory()->customer()->create();

    test()->actingAs($user)->post("/booking/{$schedule->id}", [
        'participants' => [['full_name' => 'Rina Hapsari']],
        'voucher_code' => 'UJI',
    ])->assertSessionHasErrors('voucher_code');

    expect(Booking::count())->toBe(0);
})->with([
    'kedaluwarsa' => [['valid_until' => '2020-01-01 00:00:00'], 'kedaluwarsa'],
    'belum berlaku' => [['valid_from' => '2035-01-01 00:00:00'], 'belum berlaku'],
    'nonaktif' => [['is_active' => false], 'nonaktif'],
    'kuota habis' => [['quota' => 2, 'used_count' => 2], 'kuota'],
    'minimal belanja' => [['min_spend' => 1_000_000], 'minimal'],
]);

it('menolak voucher yang cakupannya trip lain', function () {
    $schedule = jadwalUntukBooking(IdType::None, quota: 20, harga: 300_000);
    $tripLain = Trip::factory()->published()->create();

    Voucher::factory()->create([
        'code' => 'KHUSUS',
        'scope' => VoucherScope::Trip,
        'scope_id' => $tripLain->id,
    ]);

    $user = User::factory()->customer()->create();

    test()->actingAs($user)->post("/booking/{$schedule->id}", [
        'participants' => [['full_name' => 'Rina Hapsari']],
        'voucher_code' => 'KHUSUS',
    ])->assertSessionHasErrors('voucher_code');
});

it('menerima voucher yang cakupannya kategori trip itu', function () {
    $category = Category::factory()->create();
    $trip = Trip::factory()->published()->for($category)->create();
    $schedule = $trip->schedules()->create([
        'start_date' => now()->addDays(10)->toDateString(),
        'quota' => 10,
        'booked_count' => 0,
        'status' => 'published',
    ]);
    $schedule->prices()->create(['label' => 'Reguler', 'price' => 400_000, 'min_pax' => 1, 'max_pax' => null]);

    Voucher::factory()->create([
        'code' => 'KATEGORI',
        'type' => VoucherType::Percent,
        'value' => 25,
        'scope' => VoucherScope::Category,
        'scope_id' => $category->id,
    ]);

    test()->actingAs(User::factory()->customer()->create())->post("/booking/{$schedule->id}", [
        'participants' => [['full_name' => 'Rina Hapsari']],
        'voucher_code' => 'KATEGORI',
    ])->assertRedirect();

    expect(Booking::firstOrFail()->discount_amount)->toBe(100_000);
});

it('menolak voucher yang sudah dipakai user yang sama', function () {
    $schedule = jadwalUntukBooking(IdType::None, quota: 20, harga: 300_000);
    Voucher::factory()->create(['code' => 'SEKALI']);
    $user = User::factory()->customer()->create();

    test()->actingAs($user)->post("/booking/{$schedule->id}", [
        'participants' => [['full_name' => 'Rina Hapsari']],
        'voucher_code' => 'SEKALI',
    ])->assertRedirect();

    test()->actingAs($user)->post("/booking/{$schedule->id}", [
        'participants' => [['full_name' => 'Rina Hapsari']],
        'voucher_code' => 'SEKALI',
    ])->assertSessionHasErrors('voucher_code');

    expect(Booking::count())->toBe(1);
});

it('membolehkan voucher dipakai lagi kalau booking sebelumnya kedaluwarsa', function () {
    $schedule = jadwalUntukBooking(IdType::None, quota: 20, harga: 300_000);
    Voucher::factory()->create(['code' => 'ULANG']);
    $user = User::factory()->customer()->create();

    test()->actingAs($user)->post("/booking/{$schedule->id}", [
        'participants' => [['full_name' => 'Rina Hapsari']],
        'voucher_code' => 'ULANG',
    ])->assertRedirect();

    Booking::firstOrFail()->update(['status' => BookingStatus::Expired]);

    test()->actingAs($user)->post("/booking/{$schedule->id}", [
        'participants' => [['full_name' => 'Rina Hapsari']],
        'voucher_code' => 'ULANG',
    ])->assertRedirect();

    expect(Booking::count())->toBe(2);
});

it('menampilkan opsi tambahan di halaman detail trip', function () {
    [$schedule, $opsi] = jadwalDenganOpsi();

    test()->get(route('trips.show', $schedule->trip))
        ->assertOk()
        ->assertSee('Tambahan opsional')
        ->assertSee($opsi->name);
});

it('menyembunyikan opsi nonaktif dari detail trip', function () {
    [$schedule, $opsi] = jadwalDenganOpsi();
    $opsi->update(['is_active' => false]);

    test()->get(route('trips.show', $schedule->trip))
        ->assertOk()
        ->assertDontSee('Tambahan opsional');
});
