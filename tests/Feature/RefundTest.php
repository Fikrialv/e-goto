<?php

use App\Actions\ProcessRefundRequest;
use App\Enums\AdminScope;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\RefundType;
use App\Enums\UserRole;
use App\Filament\Pages\RiwayatCustomer;
use App\Filament\Resources\RefundRequestResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/**
 * D14 — Riwayat Transaksi & Refund.
 *
 * Sistem ini MENJALANKAN kebijakan refund yang sudah tertulis di GUIDE.md
 * (tiga opsi saat trip dibatalkan penyelenggara / force majeure), bukan
 * membuat kebijakan baru.
 */
beforeEach(function () {
    Storage::fake('local');
});

function bookingLunasRefund(?User $user = null): Booking
{
    $booking = bookingSiapBayar($user);
    $booking->update(['status' => BookingStatus::Confirmed]);

    Payment::create([
        'booking_id' => $booking->id,
        'method' => 'qris',
        'amount_declared' => $booking->total_amount,
        'proof_path' => 'bukti-bayar/contoh.jpg',
        'proof_hash' => hash('sha256', 'contoh-'.$booking->id),
        'status' => PaymentStatus::Verified,
    ]);

    return $booking->refresh();
}

function adminRefund(): User
{
    return User::factory()->create([
        'role' => UserRole::Admin,
        'admin_scope' => AdminScope::PaymentCs,
    ]);
}

it('menampilkan pembayaran dan refund di Riwayat Transaksi', function () {
    $booking = bookingLunasRefund();

    $this->actingAs($booking->user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertSee($booking->code)
        ->assertSee('Rp'.number_format($booking->total_amount, 0, ',', '.'))
        // Halaman ini menjawab "uang saya ke mana", Booking Saya menjawab
        // "trip saya apa" — keduanya harus tetap saling menunjuk.
        ->assertSee(route('bookings.index'), escape: false);
});

it('menolak Riwayat Transaksi untuk tamu', function () {
    $this->get(route('transactions.index'))->assertRedirect(route('login'));
});

it('tidak menampilkan transaksi customer lain', function () {
    $punyaOrangLain = bookingLunasRefund();
    $sayaSendiri = User::factory()->customer()->create();

    $this->actingAs($sayaSendiri)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertDontSee($punyaOrangLain->code);
});

it('menerima pengajuan refund dengan salah satu dari tiga opsi', function () {
    $booking = bookingLunasRefund();

    $this->actingAs($booking->user)
        ->post(route('refunds.store', $booking), [
            'type' => RefundType::Refund100->value,
            'customer_note' => 'Trip dibatalkan penyelenggara.',
        ])
        ->assertRedirect(route('transactions.index'));

    $ajuan = RefundRequest::firstOrFail();

    expect($ajuan->booking_id)->toBe($booking->id)
        ->and($ajuan->type)->toBe(RefundType::Refund100)
        ->and($ajuan->status)->toBe(RefundStatus::Diajukan);
});

it('menolak opsi refund karangan', function () {
    $booking = bookingLunasRefund();

    // Daftar opsi di HTML tidak menolak apa pun yang dikirim dengan POST buatan.
    $this->actingAs($booking->user)
        ->post(route('refunds.store', $booking), ['type' => 'uang_dobel'])
        ->assertSessionHasErrors('type');

    expect(RefundRequest::count())->toBe(0);
});

it('menolak pengajuan refund atas booking milik orang lain', function () {
    $booking = bookingLunasRefund();
    $penyusup = User::factory()->customer()->create();

    $this->actingAs($penyusup)
        ->post(route('refunds.store', $booking), ['type' => RefundType::Refund100->value])
        ->assertForbidden();

    expect(RefundRequest::count())->toBe(0);
});

it('menolak pengajuan refund atas booking yang belum dibayar', function () {
    // Tidak ada yang bisa dikembalikan dari booking yang uangnya belum masuk.
    $booking = bookingSiapBayar();

    expect($booking->bolehAjukanRefund())->toBeFalse();

    expect(fn () => app(ProcessRefundRequest::class)->ajukan($booking, RefundType::Refund100))
        ->toThrow(ValidationException::class);
});

it('menolak pengajuan kedua selama yang pertama masih berjalan', function () {
    $booking = bookingLunasRefund();

    app(ProcessRefundRequest::class)->ajukan($booking, RefundType::Refund100);

    // Pengajuan ganda bisa diproses dua admin berbeda dan menghasilkan dua
    // transfer untuk satu booking.
    expect(fn () => app(ProcessRefundRequest::class)->ajukan($booking->refresh(), RefundType::Waitlist))
        ->toThrow(ValidationException::class);

    expect(RefundRequest::count())->toBe(1);
});

it('mengizinkan pengajuan ulang setelah yang pertama ditolak', function () {
    $booking = bookingLunasRefund();
    $admin = adminRefund();

    $pertama = app(ProcessRefundRequest::class)->ajukan($booking, RefundType::Refund100);
    app(ProcessRefundRequest::class)->tolak($pertama, $admin, 'Trip tetap berangkat sesuai jadwal.');

    app(ProcessRefundRequest::class)->ajukan($booking->refresh(), RefundType::Waitlist);

    expect(RefundRequest::count())->toBe(2);
});

it('melepas kursi saat refund 100% disetujui', function () {
    $booking = bookingLunasRefund();
    $ajuan = app(ProcessRefundRequest::class)->ajukan($booking, RefundType::Refund100);

    app(ProcessRefundRequest::class)->setujui($ajuan, adminRefund());

    expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled);
});

it('tidak melepas kursi untuk opsi ganti trip dan waitlist', function () {
    // Pemindahannya dikerjakan admin manual (selisih harga case-by-case), dan
    // booking lama baru dibatalkan setelah penggantinya benar-benar ada.
    foreach ([RefundType::GantiTrip, RefundType::Waitlist] as $opsi) {
        $booking = bookingLunasRefund();
        $ajuan = app(ProcessRefundRequest::class)->ajukan($booking, $opsi);

        app(ProcessRefundRequest::class)->setujui($ajuan, adminRefund());

        expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
    }
});

it('mewajibkan alasan saat admin menolak pengajuan refund', function () {
    $booking = bookingLunasRefund();
    $ajuan = app(ProcessRefundRequest::class)->ajukan($booking, RefundType::Refund100);

    expect(fn () => app(ProcessRefundRequest::class)->tolak($ajuan, adminRefund(), '   '))
        ->toThrow(ValidationException::class);

    expect($ajuan->fresh()->status)->toBe(RefundStatus::Diajukan);
});

it('menolak keputusan kedua atas pengajuan yang sudah diputuskan', function () {
    $booking = bookingLunasRefund();
    $admin = adminRefund();
    $ajuan = app(ProcessRefundRequest::class)->ajukan($booking, RefundType::Refund100);

    app(ProcessRefundRequest::class)->setujui($ajuan, $admin);

    expect(fn () => app(ProcessRefundRequest::class)->tolak($ajuan->refresh(), $admin, 'berubah pikiran'))
        ->toThrow(ValidationException::class);
});

it('memisahkan disetujui dari selesai', function () {
    $booking = bookingLunasRefund();
    $admin = adminRefund();
    $ajuan = app(ProcessRefundRequest::class)->ajukan($booking, RefundType::Refund100);

    // Menandai selesai sebelum disetujui tidak boleh bisa — "sudah disetujui
    // tapi uangnya belum ditransfer" adalah daftar yang harus tetap ada.
    expect(fn () => app(ProcessRefundRequest::class)->tandaiSelesai($ajuan, $admin))
        ->toThrow(ValidationException::class);

    app(ProcessRefundRequest::class)->setujui($ajuan->refresh(), $admin);
    app(ProcessRefundRequest::class)->tandaiSelesai($ajuan->refresh(), $admin);

    expect($ajuan->fresh()->status)->toBe(RefundStatus::Selesai)
        ->and($ajuan->fresh()->processed_by)->toBe($admin->id);
});

it('menampilkan catatan admin ke customer setelah pengajuan ditolak', function () {
    $booking = bookingLunasRefund();
    $ajuan = app(ProcessRefundRequest::class)->ajukan($booking, RefundType::Refund100);

    app(ProcessRefundRequest::class)->tolak($ajuan, adminRefund(), 'Trip tetap berangkat sesuai jadwal.');

    $this->actingAs($booking->user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertSee('Trip tetap berangkat sesuai jadwal.');
});

it('menampilkan antrean refund di panel admin verifikator', function () {
    $booking = bookingLunasRefund();
    app(ProcessRefundRequest::class)->ajukan($booking, RefundType::Refund100);

    $this->actingAs(adminRefund())
        ->get(RefundRequestResource::getUrl('index'))
        ->assertOk()
        ->assertSee($booking->code);
});

it('menutup antrean refund dari manajer trip', function () {
    // Uang bukan urusan Manajer Trip & Mitra.
    $manajer = User::factory()->create([
        'role' => UserRole::Admin,
        'admin_scope' => AdminScope::TripManager,
    ]);

    $this->actingAs($manajer)
        ->get(RefundRequestResource::getUrl('index'))
        ->assertForbidden();
});

it('tidak memberi mitra jalan masuk ke alur refund', function () {
    // Uang masuk ke rekening E-GOTO, jadi yang mengembalikannya juga E-GOTO.
    $vendor = User::factory()->vendor()->create();

    $this->actingAs($vendor)
        ->get(RefundRequestResource::getUrl('index'))
        ->assertForbidden();
});

it('tidak menyediakan tombol buat pengajuan baru di panel admin', function () {
    // Pengajuan datang dari customer, bukan dikarang admin.
    expect(RefundRequestResource::canCreate())->toBeFalse();
});

it('menampilkan riwayat pembayaran dan refund satu customer di satu layar', function () {
    $booking = bookingLunasRefund();
    app(ProcessRefundRequest::class)->ajukan($booking, RefundType::Waitlist);

    Livewire::actingAs(adminRefund())
        ->test(RiwayatCustomer::class)
        ->set('customerId', $booking->user_id)
        ->assertSee($booking->code)
        ->assertSee('Masuk waitlist jadwal berikutnya');
});

it('menutup riwayat customer dari manajer trip', function () {
    $manajer = User::factory()->create([
        'role' => UserRole::Admin,
        'admin_scope' => AdminScope::TripManager,
    ]);

    $this->actingAs($manajer);

    expect(RiwayatCustomer::canAccess())->toBeFalse();
});
