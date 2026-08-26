<?php

use App\Enums\TripDifficulty;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Filament\Resources\TripResource\Pages\CreateTrip;
use App\Filament\Resources\TripResource\Pages\EditTrip;
use App\Filament\Resources\TripResource\RelationManagers\SchedulesRelationManager;
use App\Models\Category;
use App\Models\Trip;
use App\Models\TripSchedule;
use App\Models\User;
use Filament\Tables\Actions\CreateAction;
use Livewire\Livewire;

/**
 * CRUD trip di panel admin (D7.7) — sisa gap yang ditemukan 2026-08-24.
 *
 * Yang dijaga di sini: satu trip utuh (trip + jadwal + tingkat harga) bisa
 * dibuat dari nol tanpa membuka tinker, dan status trip tetap menentukan apa
 * yang bocor ke halaman publik.
 */
function adminTrip(): User
{
    return User::factory()->create(['role' => UserRole::Admin]);
}

it('membuat trip lengkap dari nol lewat panel admin', function () {
    $category = Category::factory()->create();

    Livewire::actingAs(adminTrip())
        ->test(CreateTrip::class)
        ->fillForm([
            'title' => 'Open Trip Gunung Andong',
            'slug' => 'open-trip-gunung-andong',
            'category_id' => $category->id,
            'meeting_point' => 'Basecamp Sawit, Magelang',
            // Draf dulu: jadwal baru bisa diisi setelah trip tersimpan, dan
            // trip published tanpa jadwal ditolak (lihat test penjaga di bawah).
            'status' => TripStatus::Draft->value,
            'difficulty_level' => TripDifficulty::Pemula->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $trip = Trip::firstOrFail();

    expect($trip->title)->toBe('Open Trip Gunung Andong')
        ->and($trip->category_id)->toBe($category->id)
        ->and($trip->vendor_id)->toBeNull()
        ->and($trip->status)->toBe(TripStatus::Draft)
        ->and($trip->difficulty_level)->toBe(TripDifficulty::Pemula);

    /*
     * Tingkat harga bersarang di form jadwal lewat Repeater::relationship().
     * Bentuk state-nya diuji langsung di sini — pola ini belum pernah dipakai
     * di project, jadi tidak boleh cuma diasumsikan sama dengan Repeater biasa.
     */
    Livewire::actingAs(adminTrip())
        ->test(SchedulesRelationManager::class, [
            'ownerRecord' => $trip,
            'pageClass' => EditTrip::class,
        ])
        ->callTableAction(CreateAction::class, data: [
            'start_date' => now()->addDays(21)->toDateString(),
            'end_date' => now()->addDays(22)->toDateString(),
            'quota' => 20,
            'status' => 'published',
            // Bentuk state Repeater::relationship() yang terbukti di sini: list
            // berindeks angka diterima SELAMA repeater-nya `defaultItems(0)`.
            // Dengan baris bawaan, Filament menyimpan state berkunci uuid dan
            // data test malah menambah baris kedua — baris bawaan yang kosong
            // itu yang kemudian gagal validasi.
            'prices' => [
                ['label' => 'Reguler', 'price' => 385_000, 'min_pax' => 1, 'max_pax' => null],
            ],
        ])
        ->assertHasNoTableActionErrors();

    $schedule = $trip->schedules()->firstOrFail();

    expect($schedule->quota)->toBe(20)
        ->and($schedule->booked_count)->toBe(0)
        ->and($schedule->status)->toBe(TripStatus::Published)
        ->and($schedule->prices()->count())->toBe(1)
        ->and($schedule->prices()->first()->price)->toBe(385_000)
        ->and($schedule->prices()->first()->min_pax)->toBe(1)
        ->and($schedule->prices()->first()->label)->toBe('Reguler');

    // Jadwal sudah ada — sekarang tripnya boleh naik ke published.
    Livewire::actingAs(adminTrip())
        ->test(EditTrip::class, ['record' => $trip->getKey()])
        ->fillForm(['status' => TripStatus::Published->value])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($trip->fresh()->status)->toBe(TripStatus::Published);
});

it('mengabaikan kursi terisi yang dikirim dari form jadwal', function () {
    $trip = Trip::factory()->published()->create();

    Livewire::actingAs(adminTrip())
        ->test(SchedulesRelationManager::class, [
            'ownerRecord' => $trip,
            'pageClass' => EditTrip::class,
        ])
        ->callTableAction(CreateAction::class, data: [
            'start_date' => now()->addDays(10)->toDateString(),
            'quota' => 15,
            'booked_count' => 12,
            'status' => 'published',
            'prices' => [
                ['label' => 'Reguler', 'price' => 250_000, 'min_pax' => 1, 'max_pax' => null],
            ],
        ])
        ->assertHasNoTableActionErrors();

    // Kursi terisi hanya boleh bergerak lewat alur booking yang mengunci baris
    // jadwal — angka dari form diabaikan, bukan dipercaya.
    expect($trip->schedules()->firstOrFail()->booked_count)->toBe(0);
});

it('menampilkan trip published yang dibuat lewat panel di halaman publik', function () {
    $category = Category::factory()->create();

    Livewire::actingAs(adminTrip())
        ->test(CreateTrip::class)
        ->fillForm([
            'title' => 'Susur Goa Pindul Sore',
            'slug' => 'susur-goa-pindul-sore',
            'category_id' => $category->id,
            'status' => TripStatus::Draft->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $trip = Trip::firstOrFail();

    TripSchedule::factory()->for($trip)->create([
        'start_date' => now()->addDays(9)->toDateString(),
        'quota' => 12,
        'booked_count' => 0,
    ]);

    Livewire::actingAs(adminTrip())
        ->test(EditTrip::class, ['record' => $trip->getKey()])
        ->fillForm(['status' => TripStatus::Published->value])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->get(route('trips.show', $trip))
        ->assertOk()
        ->assertSee('Susur Goa Pindul Sore');
});

it('menyembunyikan trip draft dari tamu', function () {
    Livewire::actingAs(adminTrip())
        ->test(CreateTrip::class)
        ->fillForm([
            'title' => 'Uji Coba Internal',
            'slug' => 'uji-coba-internal',
            'category_id' => Category::factory()->create()->id,
            'status' => TripStatus::Draft->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->get(route('trips.show', Trip::firstOrFail()))->assertNotFound();
});

it('menolak jadwal tanpa satu pun tingkat harga', function () {
    $trip = Trip::factory()->published()->create();

    Livewire::actingAs(adminTrip())
        ->test(SchedulesRelationManager::class, [
            'ownerRecord' => $trip,
            'pageClass' => EditTrip::class,
        ])
        ->callTableAction(CreateAction::class, data: [
            'start_date' => now()->addDays(10)->toDateString(),
            'quota' => 15,
            'status' => 'published',
            'prices' => [],
        ])
        ->assertHasTableActionErrors(['prices']);

    expect($trip->schedules()->count())->toBe(0);
});

it('tidak menyimpan vendor_id yang dikirim dari form trip', function () {
    $vendor = User::factory()->create(['role' => UserRole::Vendor]);

    Livewire::actingAs(adminTrip())
        ->test(CreateTrip::class)
        ->fillForm([
            'title' => 'Trip Tanpa Mitra',
            'slug' => 'trip-tanpa-mitra',
            'category_id' => Category::factory()->create()->id,
            'status' => TripStatus::Draft->value,
            'vendor_id' => $vendor->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Kolom ini akan merujuk `vendors.id` (V1.5), bukan `users.id`. Selama
    // tabelnya belum ada, nilai apa pun dari form adalah rujukan salah.
    expect(Trip::firstOrFail()->vendor_id)->toBeNull();
});

/*
 * Penjaga publikasi: trip tanpa jadwal tidak boleh naik ke `published`.
 * Kalau lolos, halaman detailnya terbuka tapi bertuliskan "belum ada jadwal",
 * sementara di halaman kategori trip itu tidak muncul sama sekali.
 */
it('menolak publikasi trip yang belum punya jadwal', function () {
    $trip = Trip::factory()->create(['status' => TripStatus::Draft]);

    Livewire::actingAs(adminTrip())
        ->test(EditTrip::class, ['record' => $trip->getKey()])
        ->fillForm(['status' => TripStatus::Published->value])
        ->call('save')
        ->assertNotified();

    expect($trip->fresh()->status)->toBe(TripStatus::Draft);
});

it('menolak trip baru yang langsung dibuat published', function () {
    Livewire::actingAs(adminTrip())
        ->test(CreateTrip::class)
        ->fillForm([
            'title' => 'Langsung Tayang',
            'slug' => 'langsung-tayang',
            'category_id' => Category::factory()->create()->id,
            'status' => TripStatus::Published->value,
        ])
        ->call('create')
        ->assertNotified();

    expect(Trip::count())->toBe(0);
});

it('membiarkan trip berjadwal disimpan ulang sebagai published', function () {
    $trip = Trip::factory()->published()->create();
    TripSchedule::factory()->for($trip)->create(['quota' => 10, 'booked_count' => 0]);

    Livewire::actingAs(adminTrip())
        ->test(EditTrip::class, ['record' => $trip->getKey()])
        ->fillForm(['meeting_point' => 'Alun-alun Batu'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($trip->fresh()->meeting_point)->toBe('Alun-alun Batu')
        ->and($trip->fresh()->status)->toBe(TripStatus::Published);
});

it('menolak tingkat harga tanpa minimal peserta', function () {
    $trip = Trip::factory()->create(['status' => TripStatus::Draft]);

    // Kolom `trip_prices.min_pax` NOT NULL — tanpa required(), field yang
    // dikosongkan mengirim null dan berakhir jadi galat database.
    Livewire::actingAs(adminTrip())
        ->test(SchedulesRelationManager::class, [
            'ownerRecord' => $trip,
            'pageClass' => EditTrip::class,
        ])
        ->callTableAction(CreateAction::class, data: [
            'start_date' => now()->addDays(10)->toDateString(),
            'quota' => 15,
            'status' => TripStatus::Published->value,
            'prices' => [
                ['label' => 'Reguler', 'price' => 250_000, 'min_pax' => null, 'max_pax' => null],
            ],
        ])
        ->assertHasTableActionErrors();

    expect($trip->schedules()->count())->toBe(0);
});

it('menolak status jadwal di luar enum', function () {
    $trip = Trip::factory()->create(['status' => TripStatus::Draft]);

    Livewire::actingAs(adminTrip())
        ->test(SchedulesRelationManager::class, [
            'ownerRecord' => $trip,
            'pageClass' => EditTrip::class,
        ])
        ->callTableAction(CreateAction::class, data: [
            'start_date' => now()->addDays(10)->toDateString(),
            'quota' => 15,
            'status' => 'publish',
            'prices' => [
                ['label' => 'Reguler', 'price' => 250_000, 'min_pax' => 1, 'max_pax' => null],
            ],
        ])
        ->assertHasTableActionErrors(['status']);

    expect($trip->schedules()->count())->toBe(0);
});
