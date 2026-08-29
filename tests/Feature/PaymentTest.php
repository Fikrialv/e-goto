<?php

use App\Actions\VerifyPayment;
use App\Enums\BookingStatus;
use App\Enums\IdType;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\PaymentResource\Pages\ReviewPayment;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Pembayaran manual & verifikasi admin (D5).
 *
 * Tiga hal yang dijaga: bukti bayar tidak boleh bisa dibaca orang lain, bukti
 * kembar harus ditandai (bukan ditolak sendiri oleh sistem), dan penolakan
 * tanpa alasan harus gagal — alasan itu satu-satunya petunjuk yang dipunya
 * customer untuk memperbaiki unggahannya.
 */
beforeEach(function () {
    Storage::fake('local');
});

/**
 * Booking siap bayar milik seorang customer.
 */
function bookingSiapBayar(?User $user = null): Booking
{
    $schedule = jadwalUntukBooking(IdType::None, quota: 20, harga: 500_000);
    $user ??= User::factory()->customer()->create();

    kirimBooking($schedule, [['full_name' => 'Peserta A']], $user)->assertRedirect();

    return Booking::latest('id')->firstOrFail();
}

function unggahBukti(Booking $booking, string $isi = 'bukti-transfer', ?User $user = null)
{
    $file = UploadedFile::fake()->createWithContent('bukti.jpg', $isi);

    return test()->actingAs($user ?? $booking->user)
        ->post("/booking/{$booking->code}/bayar", ['proof' => $file]);
}

it('menyimpan bukti bayar dan memindahkan booking ke menunggu verifikasi', function () {
    $booking = bookingSiapBayar();

    unggahBukti($booking)->assertRedirect(route('payments.show', $booking));

    $payment = Payment::firstOrFail();

    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->is_duplicate_flagged)->toBeFalse()
        ->and($payment->amount_declared)->toBe($booking->total_amount)
        ->and($booking->fresh()->status)->toBe(BookingStatus::AwaitingVerification);

    Storage::disk('local')->assertExists($payment->proof_path);
});

it('menandai bukti yang hash-nya sama dengan bukti booking lain tanpa menolaknya', function () {
    $pertama = bookingSiapBayar();
    $kedua = bookingSiapBayar();

    unggahBukti($pertama, 'isi-berkas-yang-sama-persis');
    unggahBukti($kedua, 'isi-berkas-yang-sama-persis');

    $pembayaran = Payment::orderBy('id')->get();

    expect($pembayaran[0]->is_duplicate_flagged)->toBeFalse()
        ->and($pembayaran[1]->is_duplicate_flagged)->toBeTrue()
        // Tetap tersimpan dan tetap masuk antrean — keputusan ada di admin.
        ->and($pembayaran[1]->status)->toBe(PaymentStatus::Pending)
        ->and($pembayaran[0]->proof_hash)->toBe($pembayaran[1]->proof_hash);
});

it('menolak berkas yang bukan gambar', function () {
    $booking = bookingSiapBayar();

    $this->actingAs($booking->user)
        ->post("/booking/{$booking->code}/bayar", [
            'proof' => UploadedFile::fake()->createWithContent('bukti.php', '<?php echo "halo";'),
        ])
        ->assertSessionHasErrors('proof');

    expect(Payment::count())->toBe(0);
});

it('tidak membiarkan customer lain mengunggah atau mengunduh bukti booking orang', function () {
    $booking = bookingSiapBayar();
    $penyusup = User::factory()->customer()->create();

    unggahBukti($booking);

    $this->actingAs($penyusup)->get("/booking/{$booking->code}/bukti")->assertForbidden();
    unggahBukti($booking, 'bukti-penyusup', $penyusup)->assertForbidden();

    $this->actingAs($booking->user)->get("/booking/{$booking->code}/bukti")->assertOk();
});

it('menolak pembayaran tanpa alasan', function () {
    $booking = bookingSiapBayar();
    unggahBukti($booking);

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $payment = Payment::firstOrFail();

    expect(fn () => app(VerifyPayment::class)->reject($payment, $admin, '   '))
        ->toThrow(ValidationException::class);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($booking->fresh()->status)->toBe(BookingStatus::AwaitingVerification);
});

it('mengembalikan booking ke menunggu bayar saat pembayaran ditolak dengan alasan', function () {
    $booking = bookingSiapBayar();
    unggahBukti($booking);

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $payment = Payment::firstOrFail();

    app(VerifyPayment::class)->reject($payment, $admin, 'Nominal kurang Rp123 dari yang seharusnya.');

    expect($payment->fresh()->status)->toBe(PaymentStatus::Rejected)
        ->and($payment->fresh()->reject_reason)->toBe('Nominal kurang Rp123 dari yang seharusnya.')
        ->and($payment->fresh()->verified_by)->toBe($admin->id)
        ->and($booking->fresh()->status)->toBe(BookingStatus::PendingPayment)
        // Batas bayar disegarkan supaya customer sempat mengunggah ulang.
        ->and($booking->fresh()->expires_at->isFuture())->toBeTrue();

    // Alasannya tampil di halaman pembayaran customer.
    $this->actingAs($booking->user)
        ->get("/booking/{$booking->code}/bayar")
        ->assertOk()
        ->assertSee('Nominal kurang Rp123');
});

it('menyetujui pembayaran, mengonfirmasi booking, dan melepas batas waktu', function () {
    $booking = bookingSiapBayar();
    unggahBukti($booking);

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $payment = Payment::firstOrFail();

    app(VerifyPayment::class)->approve($payment, $admin);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Verified)
        ->and($payment->fresh()->verified_at)->not->toBeNull()
        ->and($booking->fresh()->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->fresh()->expires_at)->toBeNull();
});

it('menolak keputusan kedua atas pembayaran yang sudah diputuskan', function () {
    $booking = bookingSiapBayar();
    unggahBukti($booking);

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $payment = Payment::firstOrFail();

    app(VerifyPayment::class)->approve($payment, $admin);

    expect(fn () => app(VerifyPayment::class)->reject($payment->fresh(), $admin, 'Berubah pikiran'))
        ->toThrow(ValidationException::class);

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

it('membuka layar verifikasi admin lengkap dengan bukti dan nominal, tertutup untuk non-admin', function () {
    $booking = bookingSiapBayar();
    unggahBukti($booking);

    $payment = Payment::firstOrFail();
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->get(ReviewPayment::getUrl(['record' => $payment], panel: 'admin'))
        ->assertOk()
        ->assertSee($booking->code)
        ->assertSee(number_format($booking->total_amount, 0, ',', '.'));

    // Berkas bukti hanya keluar untuk admin.
    $this->actingAs($admin)->get(route('admin.payments.proof', $payment))->assertOk();
    $this->actingAs($booking->user)->get(route('admin.payments.proof', $payment))->assertForbidden();
});

it('tidak membiarkan booking yang sudah kedaluwarsa menerima bukti bayar', function () {
    $booking = bookingSiapBayar();

    $booking->update(['status' => BookingStatus::Expired]);

    unggahBukti($booking)->assertSessionHasErrors('proof');

    expect(Payment::count())->toBe(0);
});

/*
 * Konfirmasi metode pembayaran (D7.6 f, test §9 #20). QRIS baru boleh muncul
 * setelah customer membaca bahwa pembayarannya diverifikasi manusia — bukan
 * sistem otomatis yang menerbitkan tiket sedetik setelah transfer.
 */
it('menyembunyikan kode QRIS sebelum metode pembayaran dikonfirmasi', function () {
    $booking = bookingSiapBayar();

    $this->actingAs($booking->user)
        ->get(route('payments.show', $booking))
        ->assertOk()
        ->assertSee('Saya paham, lanjutkan ke pembayaran')
        ->assertSee(config('booking.verification_eta'))
        ->assertSee(route('pages.privacy'), escape: false)
        ->assertSee(route('pages.terms'), escape: false)
        ->assertDontSee('Scan QRIS')
        ->assertDontSee('Unggah bukti pembayaran');
});

it('menampilkan QRIS dan form unggah setelah konfirmasi', function () {
    $booking = bookingSiapBayar();

    $this->actingAs($booking->user)
        ->post(route('payments.confirm', $booking))
        ->assertRedirect(route('payments.show', $booking));

    $this->actingAs($booking->user)
        ->get(route('payments.show', $booking))
        ->assertOk()
        ->assertSee('Scan QRIS')
        ->assertSee('Unduh gambar QRIS')
        ->assertSee('Unggah bukti pembayaran');
});

it('merender QR bernominal, bukan gambar statis, saat payload merchant terisi', function () {
    // Payload tiruan ber-CRC sah; isinya sama dengan yang dipakai QrisPayloadTest.
    config()->set('booking.qris_static_payload', qrisStatisContoh());

    $booking = bookingSiapBayar();

    $this->actingAs($booking->user)->post(route('payments.confirm', $booking));

    $this->actingAs($booking->user)
        ->get(route('payments.show', $booking))
        ->assertOk()
        ->assertSee('nominalnya sudah terisi otomatis', escape: false)
        ->assertSee('<svg', escape: false)
        ->assertDontSee('Unduh gambar QRIS');
});

it('meminta konfirmasi ulang untuk booking berikutnya', function () {
    $user = User::factory()->customer()->create();

    $pertama = bookingSiapBayar($user);

    $this->actingAs($user)->post(route('payments.confirm', $pertama))->assertRedirect();

    $kedua = bookingSiapBayar($user);

    $this->actingAs($user)
        ->get(route('payments.show', $kedua))
        ->assertOk()
        ->assertDontSee('Scan QRIS');
});

it('menolak konfirmasi metode pembayaran milik orang lain', function () {
    $booking = bookingSiapBayar();

    $this->actingAs(User::factory()->customer()->create())
        ->post(route('payments.confirm', $booking))
        ->assertForbidden();
});

/*
 * Badge status + estimasi verifikasi (D7.6 b, test §9 #16). Statusnya diambil
 * dari PaymentStatus yang sudah ada — tidak ada state baru.
 */
it('menampilkan estimasi verifikasi selama pembayaran masih menunggu', function () {
    $booking = bookingSiapBayar();

    unggahBukti($booking);

    $this->actingAs($booking->user)
        ->get(route('payments.show', $booking))
        ->assertOk()
        ->assertSee('Menunggu verifikasi')
        ->assertSee(config('booking.verification_eta'));
});

it('menampilkan badge terverifikasi setelah pembayaran disetujui', function () {
    $booking = bookingSiapBayar();

    unggahBukti($booking);

    app(VerifyPayment::class)->approve(
        Payment::firstOrFail(),
        User::factory()->create(['role' => UserRole::Admin]),
    );

    $this->actingAs($booking->user)
        ->get(route('payments.show', $booking))
        ->assertOk()
        ->assertSee('Pembayaran terverifikasi')
        ->assertDontSee('Menunggu verifikasi');
});
