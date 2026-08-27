<?php

use App\Enums\BookingStatus;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Filament\Resources\TripResource\Pages\ListTrips as AdminListTrips;
use App\Filament\Vendor\Resources\BookingResource\Pages\ListBookings as VendorListBookings;
use App\Filament\Vendor\Resources\TripResource\Pages\CreateTrip as VendorCreateTrip;
use App\Filament\Vendor\Resources\TripResource\Pages\EditTrip as VendorEditTrip;
use App\Filament\Vendor\Resources\TripResource\Pages\ListTrips as VendorListTrips;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Trip;
use App\Models\TripSchedule;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

/**
 * Loop mitra aktif (D9).
 *
 * Dua batas yang dijaga ketat di sini: mitra tidak boleh melihat atau menyentuh
 * trip mitra lain, dan mitra tidak boleh menayangkan tripnya sendiri — kalau
 * bisa, tinjauan admin sebelum tayang tidak ada artinya.
 */

/**
 * Panel vendor bukan panel bawaan. Komponen Filament yang diuji langsung perlu
 * diberi tahu panel mana yang aktif — kalau tidak, tautan di dalamnya dibangun
 * untuk panel admin dan render-nya gagal.
 */
function panelVendor(): void
{
    Filament::setCurrentPanel(Filament::getPanel('vendor'));
}

function mitra(): User
{
    return User::factory()->create(['role' => UserRole::Vendor]);
}

function adminPeninjau(): User
{
    return User::factory()->create(['role' => UserRole::Admin]);
}

it('mengajukan trip mitra dengan status draf dan pemilik dari sesi', function () {
    panelVendor();
    $vendor = mitra();

    Livewire::actingAs($vendor)
        ->test(VendorCreateTrip::class)
        ->fillForm([
            'title' => 'Open Trip Kawah Ijen',
            'slug' => 'open-trip-kawah-ijen',
            'category_id' => Category::factory()->create()->id,
            'status' => TripStatus::Draft->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $trip = Trip::firstOrFail();

    expect($trip->vendor_id)->toBe($vendor->id)
        ->and($trip->status)->toBe(TripStatus::Draft);
});

it('menyembunyikan trip mitra lain dari daftar mitra', function () {
    panelVendor();
    $vendor = mitra();
    $milikSendiri = Trip::factory()->create(['vendor_id' => $vendor->id, 'status' => TripStatus::Draft]);
    $milikOrangLain = Trip::factory()->create(['vendor_id' => mitra()->id, 'status' => TripStatus::Draft]);

    Livewire::actingAs($vendor)
        ->test(VendorListTrips::class)
        ->assertCanSeeTableRecords([$milikSendiri])
        ->assertCanNotSeeTableRecords([$milikOrangLain]);
});

it('menolak mitra menyunting trip mitra lain', function () {
    panelVendor();
    $trip = Trip::factory()->create(['vendor_id' => mitra()->id, 'status' => TripStatus::Draft]);

    // Scope `vendor_id` bekerja di lapisan query: record milik mitra lain
    // tidak pernah ditemukan, bukan ditemukan lalu ditolak.
    expect(fn () => Livewire::actingAs(mitra())->test(VendorEditTrip::class, ['record' => $trip->getKey()]))
        ->toThrow(ModelNotFoundException::class);
});

it('tidak memberi mitra pilihan status published', function () {
    panelVendor();
    $vendor = mitra();
    $trip = Trip::factory()->create(['vendor_id' => $vendor->id, 'status' => TripStatus::Draft]);
    TripSchedule::factory()->for($trip)->create();

    Livewire::actingAs($vendor)
        ->test(VendorEditTrip::class, ['record' => $trip->getKey()])
        ->fillForm(['status' => TripStatus::Published->value])
        ->call('save')
        ->assertHasFormErrors(['status']);

    expect($trip->fresh()->status)->toBe(TripStatus::Draft);
});

it('menolak pengajuan trip mitra yang belum punya jadwal', function () {
    panelVendor();
    $vendor = mitra();
    $trip = Trip::factory()->create(['vendor_id' => $vendor->id, 'status' => TripStatus::Draft]);

    Livewire::actingAs($vendor)
        ->test(VendorEditTrip::class, ['record' => $trip->getKey()])
        ->fillForm(['status' => TripStatus::PendingReview->value])
        ->call('save')
        ->assertNotified();

    expect($trip->fresh()->status)->toBe(TripStatus::Draft);
});

it('menayangkan trip mitra setelah admin menyetujui', function () {
    $trip = Trip::factory()->create(['vendor_id' => mitra()->id, 'status' => TripStatus::PendingReview]);
    TripSchedule::factory()->for($trip)->create(['start_date' => now()->addDays(12), 'quota' => 10, 'booked_count' => 0]);

    Livewire::actingAs(adminPeninjau())
        ->test(AdminListTrips::class)
        ->callTableAction('setujui', $trip)
        ->assertHasNoTableActionErrors();

    expect($trip->fresh()->status)->toBe(TripStatus::Published)
        ->and($trip->fresh()->published_at)->not->toBeNull();

    $this->get(route('trips.show', $trip))->assertOk();
});

it('menolak persetujuan trip yang belum punya jadwal', function () {
    $trip = Trip::factory()->create(['vendor_id' => mitra()->id, 'status' => TripStatus::PendingReview]);

    Livewire::actingAs(adminPeninjau())
        ->test(AdminListTrips::class)
        ->callTableAction('setujui', $trip)
        ->assertNotified();

    expect($trip->fresh()->status)->toBe(TripStatus::PendingReview);
});

it('mewajibkan alasan saat admin menolak pengajuan trip', function () {
    $trip = Trip::factory()->create(['vendor_id' => mitra()->id, 'status' => TripStatus::PendingReview]);
    $admin = adminPeninjau();

    Livewire::actingAs($admin)
        ->test(AdminListTrips::class)
        ->callTableAction('tolak', $trip, data: ['review_note' => ''])
        ->assertHasTableActionErrors(['review_note']);

    Livewire::actingAs($admin)
        ->test(AdminListTrips::class)
        ->callTableAction('tolak', $trip, data: ['review_note' => 'Foto sampul buram, itinerary belum jelas.'])
        ->assertHasNoTableActionErrors();

    expect($trip->fresh()->status)->toBe(TripStatus::Rejected)
        ->and($trip->fresh()->review_note)->toBe('Foto sampul buram, itinerary belum jelas.')
        ->and($trip->fresh()->reviewed_by)->toBe($admin->id);
});

it('menampilkan booking trip mitra sendiri saja', function () {
    panelVendor();
    $vendor = mitra();

    $tripSendiri = Trip::factory()->published()->create(['vendor_id' => $vendor->id]);
    $jadwalSendiri = TripSchedule::factory()->for($tripSendiri)->create(['quota' => 10, 'booked_count' => 2]);

    $tripLain = Trip::factory()->published()->create(['vendor_id' => mitra()->id]);
    $jadwalLain = TripSchedule::factory()->for($tripLain)->create(['quota' => 10, 'booked_count' => 1]);

    $customer = User::factory()->customer()->create();

    $bookingSendiri = Booking::create([
        'code' => 'EG-AAA111', 'user_id' => $customer->id, 'trip_schedule_id' => $jadwalSendiri->id,
        'pax_count' => 2, 'subtotal' => 500_000, 'discount_amount' => 0, 'unique_code' => 123,
        'total_amount' => 500_123, 'status' => BookingStatus::Confirmed,
    ]);

    $bookingLain = Booking::create([
        'code' => 'EG-BBB222', 'user_id' => $customer->id, 'trip_schedule_id' => $jadwalLain->id,
        'pax_count' => 1, 'subtotal' => 250_000, 'discount_amount' => 0, 'unique_code' => 456,
        'total_amount' => 250_456, 'status' => BookingStatus::Confirmed,
    ]);

    Livewire::actingAs($vendor)
        ->test(VendorListBookings::class)
        ->assertCanSeeTableRecords([$bookingSendiri])
        ->assertCanNotSeeTableRecords([$bookingLain]);
});
