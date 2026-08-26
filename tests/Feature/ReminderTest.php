<?php

use App\Contracts\MessagingService;
use App\Enums\BookingStatus;
use App\Enums\IdType;
use App\Enums\UserRole;
use App\Filament\Pages\ReminderKeberangkatan;
use App\Models\Booking;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

/**
 * Pengingat H-1 (D7.6 d, test §9 #18).
 *
 * Dua hal yang dijaga: antreannya benar-benar hanya berisi keberangkatan besok
 * (bukan lusa, bukan "24 jam ke depan"), dan pesannya bersih dari NIK/paspor —
 * begitu pesan keluar lewat wa.me, isinya di luar kendali kita.
 */
function bookingBerangkat(string $tanggal, BookingStatus $status = BookingStatus::Confirmed, IdType $idRequirement = IdType::None): Booking
{
    $schedule = jadwalUntukBooking($idRequirement, quota: 20, harga: 500_000);
    $schedule->update(['start_date' => $tanggal]);

    kirimBooking($schedule, [[
        'full_name' => 'Rina Hapsari',
        'phone' => '081234567890',
        'id_number' => $idRequirement === IdType::Nik ? '3201234567890123' : null,
    ]])->assertRedirect();

    $booking = Booking::latest('id')->firstOrFail();
    $booking->update(['status' => $status]);

    return $booking;
}

it('memuat booking yang berangkat besok, bukan lusa', function () {
    $besok = bookingBerangkat(today()->addDay()->toDateString());
    $lusa = bookingBerangkat(today()->addDays(2)->toDateString());

    Livewire::actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->test(ReminderKeberangkatan::class)
        ->assertCanSeeTableRecords([$besok])
        ->assertCanNotSeeTableRecords([$lusa]);
});

it('tidak memuat booking yang belum terkonfirmasi', function () {
    $belumBayar = bookingBerangkat(today()->addDay()->toDateString(), BookingStatus::PendingPayment);

    Livewire::actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->test(ReminderKeberangkatan::class)
        ->assertCanNotSeeTableRecords([$belumBayar]);
});

it('menyusun pesan pengingat tanpa nomor identitas peserta', function () {
    $category = Category::factory()->create([
        'id_requirement' => IdType::Nik,
        'gear_checklist' => ['Tenda', 'Jas hujan'],
    ]);

    $booking = bookingBerangkat(today()->addDay()->toDateString(), idRequirement: IdType::Nik);
    $booking->schedule->trip->update(['category_id' => $category->id, 'meeting_point' => 'Terminal Bungurasih']);

    $pesan = urldecode(app(MessagingService::class)->remindDayBefore($booking->fresh()->load(['schedule.trip.category', 'participants', 'user'])));

    expect($pesan)->toContain($booking->code)
        ->and($pesan)->toContain('Terminal Bungurasih')
        ->and($pesan)->toContain('Jas hujan')
        ->and($pesan)->not->toContain('3201234567890123')
        ->and($pesan)->toStartWith('https://wa.me/6281234567890');
});
