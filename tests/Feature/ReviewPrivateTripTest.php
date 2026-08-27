<?php

use App\Enums\BookingStatus;
use App\Enums\IdType;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Filament\Resources\ReviewResource\Pages\ListReviews as AdminListReviews;
use App\Filament\Vendor\Resources\ReviewResource\Pages\ListReviews;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Trip;
use App\Models\User;
use Livewire\Livewire;

/**
 * Rating & komentar (D11) + private trip dan widget chat (D12).
 *
 * Yang dijaga: review hanya lahir dari booking selesai milik sendiri, review
 * yang disembunyikan admin benar-benar hilang dari halaman publik, dan widget
 * chat tidak dirender sebelum kredensialnya dipasang.
 */
function bookingSelesai(?User $user = null): Booking
{
    $schedule = jadwalUntukBooking(IdType::None, quota: 20, harga: 400_000);
    $user ??= User::factory()->customer()->create();

    kirimBooking($schedule, [['full_name' => 'Rina Hapsari']], $user)->assertRedirect();

    $booking = Booking::latest('id')->firstOrFail();
    $booking->update(['status' => BookingStatus::Completed]);

    return $booking;
}

it('menyimpan penilaian dari booking yang sudah selesai', function () {
    $booking = bookingSelesai();

    test()->actingAs($booking->user)
        ->post(route('reviews.store', $booking), ['rating' => 5, 'comment' => 'Pemandunya sabar.'])
        ->assertRedirect(route('bookings.index'));

    $review = Review::firstOrFail();

    expect($review->rating)->toBe(5)
        ->and($review->trip_id)->toBe($booking->schedule->trip_id)
        ->and($review->status)->toBe(ReviewStatus::Published);
});

it('menolak penilaian untuk booking yang belum selesai', function () {
    $booking = bookingSelesai();
    $booking->update(['status' => BookingStatus::Confirmed]);

    test()->actingAs($booking->user)
        ->post(route('reviews.store', $booking), ['rating' => 5])
        ->assertForbidden();

    expect(Review::count())->toBe(0);
});

it('menolak penilaian kedua untuk booking yang sama', function () {
    $booking = bookingSelesai();

    test()->actingAs($booking->user)->post(route('reviews.store', $booking), ['rating' => 4])->assertRedirect();
    test()->actingAs($booking->user)->post(route('reviews.store', $booking), ['rating' => 1])->assertForbidden();

    expect(Review::count())->toBe(1);
});

it('menolak penilaian atas booking orang lain', function () {
    $booking = bookingSelesai();

    test()->actingAs(User::factory()->customer()->create())
        ->post(route('reviews.store', $booking), ['rating' => 1])
        ->assertForbidden();
});

it('menampilkan rata-rata dan daftar penilaian di detail trip', function () {
    $booking = bookingSelesai();
    $trip = $booking->schedule->trip;

    Review::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'user_id' => $booking->user_id,
        'rating' => 4,
        'comment' => 'Sunrise-nya sepadan.',
    ]);

    test()->get(route('trips.show', $trip))
        ->assertOk()
        ->assertSee('Kata peserta')
        ->assertSee('Sunrise-nya sepadan.');
});

it('menyembunyikan review yang di-hide admin dari halaman publik', function () {
    $booking = bookingSelesai();
    $trip = $booking->schedule->trip;

    $review = Review::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'user_id' => $booking->user_id,
        'comment' => 'Komentar kasar yang disembunyikan.',
    ]);

    Livewire::actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->test(AdminListReviews::class)
        ->callTableAction('sembunyikan', $review)
        ->assertHasNoTableActionErrors();

    expect($review->fresh()->status)->toBe(ReviewStatus::Hidden);

    test()->get(route('trips.show', $trip))
        ->assertOk()
        ->assertDontSee('Komentar kasar yang disembunyikan.');
});

it('membuka halaman private trip untuk tamu', function () {
    test()->get(route('private-trip.show'))
        ->assertOk()
        ->assertSee('Bikin trip sendiri');
});

it('menyiapkan tautan whatsapp berisi isian private trip', function () {
    test()->post(route('private-trip.store'), [
        'contact_name' => 'Rina Hapsari',
        'destination' => 'Bromo 2 hari',
        'pax' => 20,
        'notes' => 'Rombongan kantor.',
    ])->assertRedirect(route('private-trip.show'));

    $tautan = urldecode(session('tautanWhatsApp'));

    expect($tautan)->toStartWith('https://wa.me/')
        ->and($tautan)->toContain('Bromo 2 hari')
        ->and($tautan)->toContain('Rina Hapsari')
        ->and($tautan)->toContain('20 orang');
});

it('menolak private trip tanpa tujuan', function () {
    test()->post(route('private-trip.store'), ['contact_name' => 'Rina'])
        ->assertSessionHasErrors('destination');
});

it('menautkan teaser mitra dari homepage', function () {
    test()->get('/')
        ->assertOk()
        ->assertSee('Punya trip sendiri?')
        ->assertSee(route('partners.show'), escape: false);
});

it('tidak merender widget chat sebelum kredensialnya dipasang', function () {
    config()->set('partner.chat_widget_id', null);

    test()->get('/')
        ->assertOk()
        ->assertDontSee('embed.tawk.to')
        ->assertDontSee('client.crisp.chat')
        ->assertDontSee('Lanjut ke WhatsApp');
});

it('merender widget chat beserta tombol pindah kanal setelah id diisi', function () {
    config()->set('partner.chat_widget_id', 'abc123');
    config()->set('partner.chat_widget_provider', 'tawkto');

    test()->get('/')
        ->assertOk()
        ->assertSee('embed.tawk.to', escape: false)
        ->assertSee('Lanjut ke WhatsApp');
});

it('menyebut penyedia widget chat di kebijakan privasi saat aktif', function () {
    config()->set('partner.chat_widget_id', 'abc123');

    test()->get(route('pages.privacy'))
        ->assertOk()
        ->assertSee('Tawk.to');
});

it('menampilkan rating trip mitra di panel mitra', function () {
    $vendor = User::factory()->create(['role' => UserRole::Vendor]);
    $booking = bookingSelesai();
    $trip = $booking->schedule->trip;
    $trip->update(['vendor_id' => $vendor->id]);

    $milikMitra = Review::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'user_id' => $booking->user_id,
        'comment' => 'Ulasan trip mitra ini.',
    ]);

    $bookingLain = bookingSelesai();
    $reviewLain = Review::factory()->create([
        'booking_id' => $bookingLain->id,
        'trip_id' => $bookingLain->schedule->trip_id,
        'user_id' => $bookingLain->user_id,
        'comment' => 'Ulasan trip milik orang lain.',
    ]);

    Livewire::actingAs($vendor)
        ->test(ListReviews::class)
        ->assertCanSeeTableRecords([$milikMitra])
        ->assertCanNotSeeTableRecords([$reviewLain]);
});

it('menyembunyikan review yang di-hide dari panel mitra', function () {
    $vendor = User::factory()->create(['role' => UserRole::Vendor]);
    $booking = bookingSelesai();
    $trip = $booking->schedule->trip;
    $trip->update(['vendor_id' => $vendor->id]);

    $review = Review::factory()->hidden()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'user_id' => $booking->user_id,
    ]);

    Livewire::actingAs($vendor)
        ->test(ListReviews::class)
        ->assertCanNotSeeTableRecords([$review]);
});
