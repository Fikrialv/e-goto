<?php

use App\Actions\CheckInTicket;
use App\Actions\IssueTickets;
use App\Actions\VerifyPayment;
use App\Enums\BookingStatus;
use App\Enums\IdType;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Filament\Vendor\Pages\CheckIn;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Tiket, e-tiket, dan check-in (D6).
 *
 * Dua serangan yang dijaga: satu tiket dipakai berkali-kali, dan tiket yang
 * isinya dikarang/diubah. Keduanya diuji lewat Action yang sama yang dipakai
 * panel vendor, bukan lewat jalan pintas di test.
 */
beforeEach(function () {
    Storage::fake('local');
});

/**
 * Booking terkonfirmasi (lewat jalur asli: booking → bukti bayar → approve),
 * beserta tiket yang terbit.
 */
function bookingTerkonfirmasi(int $pax = 2, ?User $vendor = null): Booking
{
    $schedule = jadwalUntukBooking(IdType::None, quota: 20, harga: 500_000);

    if ($vendor) {
        $schedule->trip->update(['vendor_id' => $vendor->id]);
    }

    $peserta = collect(range(1, $pax))
        ->map(fn (int $i) => ['full_name' => "Peserta {$i}"])
        ->all();

    kirimBooking($schedule, $peserta)->assertRedirect();

    $booking = Booking::latest('id')->firstOrFail();
    unggahBukti($booking);

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    app(VerifyPayment::class)->approve(Payment::latest('id')->firstOrFail(), $admin);

    return $booking->fresh();
}

it('menerbitkan satu tiket bertanda tangan untuk tiap peserta saat pembayaran disetujui', function () {
    $booking = bookingTerkonfirmasi(pax: 3);

    expect($booking->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->tickets()->count())->toBe(3);

    $ticket = $booking->tickets()->first();

    expect(strlen($ticket->token))->toBe(32)
        ->and($ticket->signature)->not->toBeEmpty()
        ->and($ticket->status)->toBe(TicketStatus::Issued)
        ->and($ticket->signature)->toBe(
            hash_hmac('sha256', $ticket->token.'|'.$booking->code.'|'.$ticket->participant_id, config('app.key'))
        );
});

it('tidak menggandakan tiket kalau penerbitan dipanggil dua kali', function () {
    $booking = bookingTerkonfirmasi(pax: 2);

    app(IssueTickets::class)->handle($booking);

    expect($booking->tickets()->count())->toBe(2);
});

it('menampilkan e-tiket ke pemiliknya dan menutupnya untuk orang lain', function () {
    $booking = bookingTerkonfirmasi(pax: 1);
    $penyusup = User::factory()->customer()->create();

    $this->actingAs($booking->user)
        ->get("/booking/{$booking->code}/tiket")
        ->assertOk()
        ->assertSee($booking->tickets()->first()->token);

    $this->actingAs($penyusup)->get("/booking/{$booking->code}/tiket")->assertForbidden();
});

it('tidak menerbitkan e-tiket untuk booking yang belum dibayar', function () {
    $schedule = jadwalUntukBooking(IdType::None, quota: 10);
    $user = User::factory()->customer()->create();

    kirimBooking($schedule, [['full_name' => 'Peserta A']], $user)->assertRedirect();

    $booking = Booking::latest('id')->firstOrFail();

    $this->actingAs($user)->get("/booking/{$booking->code}/tiket")->assertNotFound();
});

it('menerima check-in tiket sah dan menolak pemakaian keduanya', function () {
    $vendor = User::factory()->create(['role' => UserRole::Vendor]);
    $booking = bookingTerkonfirmasi(pax: 1, vendor: $vendor);
    $ticket = $booking->tickets()->first();

    $hasil = app(CheckInTicket::class)->handle($ticket->token, $vendor);

    expect($hasil->status)->toBe(TicketStatus::Used)
        ->and($hasil->checked_in_by)->toBe($vendor->id)
        ->and($hasil->checked_in_at)->not->toBeNull();

    expect(fn () => app(CheckInTicket::class)->handle($ticket->token, $vendor))
        ->toThrow(ValidationException::class, 'sudah dipakai');
});

it('menolak tiket yang tanda tangannya dipalsukan', function () {
    $vendor = User::factory()->create(['role' => UserRole::Vendor]);
    $booking = bookingTerkonfirmasi(pax: 1, vendor: $vendor);
    $ticket = $booking->tickets()->first();

    // Tanda tangan diutak-atik langsung di database, seolah penyerang punya
    // akses tulis ke baris tiket tapi tidak punya APP_KEY.
    $ticket->update(['signature' => str_repeat('a', 64)]);

    expect(fn () => app(CheckInTicket::class)->handle($ticket->token, $vendor))
        ->toThrow(ValidationException::class, 'tidak valid');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Issued);
});

it('menolak token yang sama sekali tidak ada', function () {
    $vendor = User::factory()->create(['role' => UserRole::Vendor]);

    expect(fn () => app(CheckInTicket::class)->handle(str_repeat('z', 32), $vendor))
        ->toThrow(ValidationException::class, 'tidak valid');
});

it('menolak vendor yang men-check-in peserta trip milik orang lain', function () {
    $pemilikTrip = User::factory()->create(['role' => UserRole::Vendor]);
    $vendorLain = User::factory()->create(['role' => UserRole::Vendor]);

    $booking = bookingTerkonfirmasi(pax: 1, vendor: $pemilikTrip);
    $ticket = $booking->tickets()->first();

    expect(fn () => app(CheckInTicket::class)->handle($ticket->token, $vendorLain))
        ->toThrow(ValidationException::class, 'bukan untuk trip Anda');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Issued);
});

it('membiarkan admin men-check-in tiket trip milik E-GOTO', function () {
    $booking = bookingTerkonfirmasi(pax: 1);
    $ticket = $booking->tickets()->first();
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    expect(app(CheckInTicket::class)->handle($ticket->token, $admin)->status)->toBe(TicketStatus::Used);
});

it('membuka halaman check-in vendor hanya untuk vendor', function () {
    $vendor = User::factory()->create(['role' => UserRole::Vendor]);
    $customer = User::factory()->customer()->create();

    $url = CheckIn::getUrl(panel: 'vendor');

    $this->actingAs($vendor)->get($url)->assertOk();
    $this->actingAs($customer)->get($url)->assertForbidden();
});

it('memproses check-in lewat form panel vendor, bukan hanya lewat Action', function () {
    $vendor = User::factory()->create(['role' => UserRole::Vendor]);
    $booking = bookingTerkonfirmasi(pax: 1, vendor: $vendor);
    $ticket = $booking->tickets()->first();

    Livewire::actingAs($vendor)
        ->test(CheckIn::class)
        ->fillForm(['token' => $ticket->token])
        ->call('checkIn')
        ->assertHasNoFormErrors();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Used);

    // Pemakaian kedua lewat form: ditolak, dan pesannya menyebut sudah dipakai.
    Livewire::actingAs($vendor)
        ->test(CheckIn::class)
        ->fillForm(['token' => $ticket->token])
        ->call('checkIn')
        ->assertSee('sudah dipakai');
});

it('tidak membiarkan tiket dipakai kalau bookingnya dibatalkan setelah tiket terbit', function () {
    $vendor = User::factory()->create(['role' => UserRole::Vendor]);
    $booking = bookingTerkonfirmasi(pax: 1, vendor: $vendor);
    $ticket = $booking->tickets()->first();

    $booking->update(['status' => BookingStatus::Cancelled]);

    expect(fn () => app(CheckInTicket::class)->handle($ticket->token, $vendor))
        ->toThrow(ValidationException::class, 'belum terkonfirmasi');

    expect(Ticket::find($ticket->id)->status)->toBe(TicketStatus::Issued);
});
