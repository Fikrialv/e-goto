<?php

use App\Enums\BookingStatus;
use App\Enums\IdType;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Trip;
use App\Models\TripPrice;
use App\Models\TripSchedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Alur pemesanan (D4).
 *
 * Yang dijaga di sini adalah tiga hal yang mahal kalau salah: identitas peserta
 * (wajib sesuai kategori, tersimpan terenkripsi), kuota (tidak boleh tembus),
 * dan pelepasan kursi saat booking tidak dibayar.
 */
function jadwalUntukBooking(IdType $idRequirement = IdType::None, int $quota = 10, int $booked = 0, int $harga = 500_000): TripSchedule
{
    $category = Category::factory()->create(['id_requirement' => $idRequirement]);
    $trip = Trip::factory()->published()->for($category)->create();

    $schedule = TripSchedule::factory()->for($trip)->create([
        'start_date' => now()->addDays(14)->toDateString(),
        'quota' => $quota,
        'booked_count' => $booked,
    ]);

    TripPrice::factory()->for($schedule, 'schedule')->create([
        'price' => $harga,
        'min_pax' => 1,
        'max_pax' => null,
    ]);

    return $schedule;
}

/**
 * @param  array<int, array<string, string>>  $participants
 */
function kirimBooking(TripSchedule $schedule, array $participants, ?User $user = null)
{
    $user ??= User::factory()->customer()->create();

    return test()->actingAs($user)->post("/booking/{$schedule->id}", [
        'participants' => $participants,
    ]);
}

it('membuat booking beserta peserta, nominal unik, dan menahan kuota', function () {
    $schedule = jadwalUntukBooking(harga: 500_000);
    $user = User::factory()->customer()->create();

    kirimBooking($schedule, [
        ['full_name' => 'Rina Hapsari', 'phone' => '08123456789'],
        ['full_name' => 'Bagas Prakoso'],
    ], $user)->assertRedirect();

    $booking = Booking::firstOrFail();

    expect($booking->pax_count)->toBe(2)
        ->and($booking->subtotal)->toBe(1_000_000)
        ->and($booking->unique_code)->toBeGreaterThan(0)
        ->and($booking->total_amount)->toBe(1_000_000 + $booking->unique_code)
        ->and($booking->status)->toBe(BookingStatus::PendingPayment)
        ->and($booking->expires_at->diffInMinutes(now(), absolute: true))->toBeLessThanOrEqual(121)
        ->and($booking->participants()->count())->toBe(2)
        ->and($booking->participants()->where('is_leader', true)->count())->toBe(1)
        ->and($schedule->fresh()->booked_count)->toBe(2);
});

it('menolak booking kategori pendakian yang tidak menyertakan NIK', function () {
    $schedule = jadwalUntukBooking(IdType::Nik);

    kirimBooking($schedule, [
        ['full_name' => 'Rina Hapsari'],
    ])->assertSessionHasErrors('participants.0.id_number');

    expect(Booking::count())->toBe(0)
        ->and($schedule->fresh()->booked_count)->toBe(0);
});

it('menolak NIK yang bukan 16 digit', function () {
    $schedule = jadwalUntukBooking(IdType::Nik);

    kirimBooking($schedule, [
        ['full_name' => 'Rina Hapsari', 'id_number' => '12345'],
    ])->assertSessionHasErrors('participants.0.id_number');

    expect(Booking::count())->toBe(0);
});

it('menyimpan NIK terenkripsi dan tidak menyisakan angkanya di kolom database', function () {
    $schedule = jadwalUntukBooking(IdType::Nik);
    $nik = '3204123456780001';

    kirimBooking($schedule, [
        ['full_name' => 'Rina Hapsari', 'id_number' => $nik],
    ])->assertRedirect();

    $mentah = DB::table('booking_participants')->first();

    expect($mentah->id_number)->not->toContain($nik)
        ->and($mentah->id_number_hash)->toBe(hash('sha256', $nik));

    // Lewat model, nomornya kembali utuh — enkripsinya transparan bagi aplikasi.
    expect(Booking::firstOrFail()->participants()->first()->id_number)->toBe($nik);
});

it('menolak booking yang melebihi sisa kuota tanpa mengubah hitungan kursi', function () {
    $schedule = jadwalUntukBooking(quota: 10, booked: 8);

    kirimBooking($schedule, [
        ['full_name' => 'Peserta A'],
        ['full_name' => 'Peserta B'],
        ['full_name' => 'Peserta C'],
    ])->assertSessionHasErrors('pax_count');

    expect(Booking::count())->toBe(0)
        ->and($schedule->fresh()->booked_count)->toBe(8);
});

it('menutup halaman booking untuk jadwal yang sudah penuh', function () {
    $schedule = jadwalUntukBooking(quota: 5, booked: 5);
    $user = User::factory()->customer()->create();

    $this->actingAs($user)->get("/booking/{$schedule->id}")->assertNotFound();
});

it('memakai harga bertingkat sesuai jumlah peserta', function () {
    $schedule = jadwalUntukBooking(harga: 600_000);

    // Tingkat rombongan: 3 orang ke atas lebih murah per kepala.
    TripPrice::factory()->for($schedule, 'schedule')->create([
        'price' => 450_000,
        'min_pax' => 3,
        'max_pax' => null,
    ]);

    kirimBooking($schedule, [
        ['full_name' => 'Peserta A'],
        ['full_name' => 'Peserta B'],
        ['full_name' => 'Peserta C'],
    ])->assertRedirect();

    expect(Booking::firstOrFail()->subtotal)->toBe(1_350_000);
});

it('memberi nominal unik berbeda untuk dua booking dengan subtotal sama', function () {
    $schedule = jadwalUntukBooking(quota: 20, harga: 500_000);

    kirimBooking($schedule, [['full_name' => 'Peserta A']]);
    kirimBooking($schedule, [['full_name' => 'Peserta B']]);

    $kode = Booking::pluck('unique_code');

    expect($kode)->toHaveCount(2)
        ->and($kode->unique())->toHaveCount(2);
});

it('mengedaluwarsakan booking yang lewat batas bayar dan mengembalikan kuotanya', function () {
    $schedule = jadwalUntukBooking(quota: 10);

    kirimBooking($schedule, [
        ['full_name' => 'Peserta A'],
        ['full_name' => 'Peserta B'],
    ])->assertRedirect();

    expect($schedule->fresh()->booked_count)->toBe(2);

    // Lewat dua jam: batas bayar habis.
    $this->travel(3)->hours();

    $this->artisan('bookings:expire')->assertSuccessful();

    expect(Booking::firstOrFail()->status)->toBe(BookingStatus::Expired)
        ->and($schedule->fresh()->booked_count)->toBe(0);
});

it('tidak mengedaluwarsakan booking yang belum lewat batas bayar', function () {
    $schedule = jadwalUntukBooking();

    kirimBooking($schedule, [['full_name' => 'Peserta A']])->assertRedirect();

    $this->travel(30)->minutes();
    $this->artisan('bookings:expire')->assertSuccessful();

    expect(Booking::firstOrFail()->status)->toBe(BookingStatus::PendingPayment)
        ->and($schedule->fresh()->booked_count)->toBe(1);
});

it('menolak pemilik lain membuka halaman pembayaran booking', function () {
    $schedule = jadwalUntukBooking();
    $pemilik = User::factory()->customer()->create();
    $penyusup = User::factory()->customer()->create();

    kirimBooking($schedule, [['full_name' => 'Peserta A']], $pemilik)->assertRedirect();

    $booking = Booking::firstOrFail();

    $this->actingAs($pemilik)->get("/booking/{$booking->code}/bayar")->assertOk();
    $this->actingAs($penyusup)->get("/booking/{$booking->code}/bayar")->assertForbidden();
});

/*
 * Cap keras 12 peserta (PLAN.md §5.6). Dua sisi yang dijaga: batasnya
 * ditegakkan server (bukan cuma di form, yang bisa dilewati POST manual),
 * dan orang yang mentok diberi tahu jalan keluarnya.
 */
it('menolak booking di atas batas peserta walau kuota masih longgar', function () {
    $maks = config('booking.max_pax_per_booking');
    $schedule = jadwalUntukBooking(quota: 30);

    $peserta = collect(range(1, $maks + 1))
        ->map(fn (int $i) => ['full_name' => "Peserta {$i}"])
        ->all();

    kirimBooking($schedule, $peserta)->assertSessionHasErrors('participants');

    expect(Booking::count())->toBe(0)
        ->and($schedule->fresh()->booked_count)->toBe(0);
});

it('menerima booking tepat di batas peserta', function () {
    $maks = config('booking.max_pax_per_booking');
    $schedule = jadwalUntukBooking(quota: 30);

    $peserta = collect(range(1, $maks))
        ->map(fn (int $i) => ['full_name' => "Peserta {$i}"])
        ->all();

    kirimBooking($schedule, $peserta)->assertRedirect();

    expect(Booking::firstOrFail()->pax_count)->toBe($maks)
        ->and($schedule->fresh()->booked_count)->toBe($maks);
});

it('menawarkan private trip di form booking saat sisa kuota melebihi batas peserta', function () {
    $schedule = jadwalUntukBooking(quota: 30);
    $user = User::factory()->customer()->create();

    $this->actingAs($user)
        ->get("/booking/{$schedule->id}")
        ->assertOk()
        ->assertSee('private trip')
        ->assertSee('wa.me/'.config('booking.admin_whatsapp'), escape: false);
});
