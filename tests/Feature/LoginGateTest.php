<?php

use App\Enums\UserRole;
use App\Models\Trip;
use App\Models\TripPrice;
use App\Models\TripSchedule;
use App\Models\User;

/**
 * Gerbang login (PLAN.md §5.5) — kriteria selesai D3.
 *
 * Yang dijaga: tamu yang menekan tombol booking harus mendarat kembali di
 * jadwal yang SAMA setelah masuk atau mendaftar. Kalau suatu saat ada yang
 * mengganti `intended()` jadi redirect tetap, test ini yang menangkap.
 */
function jadwalSiapBooking(): TripSchedule
{
    $trip = Trip::factory()->published()->create();

    $schedule = TripSchedule::factory()->for($trip)->create([
        'start_date' => now()->addDays(21)->toDateString(),
        'quota' => 20,
        'booked_count' => 0,
    ]);

    TripPrice::factory()->for($schedule, 'schedule')->create(['price' => 750_000]);

    return $schedule;
}

it('mengusir tamu dari halaman booking ke halaman masuk', function () {
    $schedule = jadwalSiapBooking();

    $this->get("/booking/{$schedule->id}")->assertRedirect(route('login'));
});

it('mengembalikan tamu ke booking yang sama setelah masuk', function () {
    $schedule = jadwalSiapBooking();
    User::factory()->customer()->create(['email' => 'rina@contoh.test']);

    $this->get("/booking/{$schedule->id}")->assertRedirect(route('login'));

    $this->post('/masuk', ['email' => 'rina@contoh.test', 'password' => 'password'])
        ->assertRedirect("/booking/{$schedule->id}");

    $this->get("/booking/{$schedule->id}")
        ->assertOk()
        ->assertSee($schedule->trip->title);
});

it('mengembalikan tamu ke booking yang sama lewat jalur daftar dan melewati profil', function () {
    $schedule = jadwalSiapBooking();

    $this->get("/booking/{$schedule->id}")->assertRedirect(route('login'));

    // Mendaftar tidak boleh mengonsumsi tujuan tadi — layar profil masih di tengah.
    $this->post('/daftar', [
        'name' => 'Sinta',
        'email' => 'sinta@contoh.test',
        'password' => 'rahasia-kuat-123',
        'password_confirmation' => 'rahasia-kuat-123',
    ])->assertRedirect(route('profile.complete'));

    $this->get('/profil/lewati')->assertRedirect("/booking/{$schedule->id}");
});

it('mengembalikan tamu ke booking yang sama setelah menyimpan profil', function () {
    $schedule = jadwalSiapBooking();

    $this->get("/booking/{$schedule->id}");

    $this->post('/daftar', [
        'name' => 'Sinta',
        'email' => 'sinta2@contoh.test',
        'password' => 'rahasia-kuat-123',
        'password_confirmation' => 'rahasia-kuat-123',
    ]);

    $this->put('/profil', ['full_name' => 'Sinta Dewi', 'phone' => '08123456789'])
        ->assertRedirect("/booking/{$schedule->id}");

    $this->assertDatabaseHas('customer_profiles', ['full_name' => 'Sinta Dewi']);
});

it('menutup halaman profil dan booking saya dari tamu', function () {
    $this->get('/profil')->assertRedirect(route('login'));
    $this->get('/booking-saya')->assertRedirect(route('login'));
});

it('menolak vendor membuka area customer', function () {
    $vendor = User::factory()->create(['role' => UserRole::Vendor]);

    $this->actingAs($vendor)->get('/booking-saya')->assertForbidden();
    $this->actingAs($vendor)->get('/profil')->assertForbidden();
});

it('menolak booking untuk jadwal yang sudah penuh', function () {
    $schedule = jadwalSiapBooking();
    $schedule->update(['booked_count' => $schedule->quota]);

    $this->actingAs(User::factory()->customer()->create())
        ->get("/booking/{$schedule->id}")
        ->assertNotFound();
});

it('tetap membuka detail trip untuk tamu walau CTA-nya menuju halaman terkunci', function () {
    $schedule = jadwalSiapBooking();

    $this->get("/trip/{$schedule->trip->slug}")
        ->assertOk()
        ->assertSee('Booking tanggal ini');
});
