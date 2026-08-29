<?php

use App\Enums\UserRole;
use App\Filament\Pages\Keamanan;
use App\Http\Middleware\RequireTwoFactor;
use App\Models\User;
use App\Services\TwoFactor;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

/**
 * Verifikasi dua langkah untuk akun staf (2026-08-29).
 *
 * Akun admin memegang tombol approve pembayaran dan penerbitan tiket — satu
 * kata sandi yang bocor cukup untuk menyetujui pembayaran yang tidak pernah
 * masuk. Yang diuji di sini adalah bahwa langkah kedua benar-benar menahan,
 * bukan sekadar tersedia.
 */
function adminDenganTwoFactor(string $rahasia): User
{
    return User::factory()->create([
        'role' => UserRole::Admin,
        'two_factor_secret' => $rahasia,
        'two_factor_recovery_codes' => ['AAAAA-BBBBB', 'CCCCC-DDDDD'],
        'two_factor_confirmed_at' => now(),
    ]);
}

function kodeSaatIni(string $rahasia): string
{
    return app(Google2FA::class)->getCurrentOtp($rahasia);
}

it('membiarkan admin tanpa 2FA masuk panel seperti biasa', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    // Memaksa 2FA ke seluruh akun staf sekaligus akan mengunci pemilik project
    // keluar begitu migration jalan.
    $this->actingAs($admin)->get('/admin')->assertSuccessful();
});

it('menahan panel admin sampai kode dimasukkan', function () {
    $rahasia = app(TwoFactor::class)->rahasiaBaru();

    $this->actingAs(adminDenganTwoFactor($rahasia))
        ->get('/admin')
        ->assertRedirect(route('two-factor.challenge'));
});

it('meloloskan panel setelah kode benar', function () {
    $rahasia = app(TwoFactor::class)->rahasiaBaru();
    $admin = adminDenganTwoFactor($rahasia);

    $this->actingAs($admin)
        ->post(route('two-factor.challenge.store'), ['code' => kodeSaatIni($rahasia)])
        ->assertRedirect();

    expect(session(RequireTwoFactor::KUNCI_SESI))->toBe($admin->id);

    $this->actingAs($admin)->get('/admin')->assertSuccessful();
});

it('menolak kode yang salah', function () {
    $rahasia = app(TwoFactor::class)->rahasiaBaru();
    $admin = adminDenganTwoFactor($rahasia);

    $this->actingAs($admin)
        ->post(route('two-factor.challenge.store'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    expect(session(RequireTwoFactor::KUNCI_SESI))->toBeNull();
});

it('menerima kode pemulihan dan membuangnya setelah dipakai', function () {
    $rahasia = app(TwoFactor::class)->rahasiaBaru();
    $admin = adminDenganTwoFactor($rahasia);

    $this->actingAs($admin)
        ->post(route('two-factor.challenge.store'), ['code' => 'AAAAA-BBBBB'])
        ->assertRedirect();

    // Kode yang tetap berlaku setelah dipakai sama saja dengan kata sandi kedua
    // yang tercetak di kertas.
    expect($admin->fresh()->two_factor_recovery_codes)->toBe(['CCCCC-DDDDD']);

    $this->actingAs($admin)
        ->post(route('two-factor.challenge.store'), ['code' => 'AAAAA-BBBBB'])
        ->assertSessionHasErrors('code');
});

it('mengunci percobaan setelah lima kode salah', function () {
    $rahasia = app(TwoFactor::class)->rahasiaBaru();
    $admin = adminDenganTwoFactor($rahasia);

    // Enam digit hanya punya satu juta kemungkinan — tanpa pembatasan laju,
    // menebaknya jadi pekerjaan skrip.
    foreach (range(1, 5) as $i) {
        $this->actingAs($admin)->post(route('two-factor.challenge.store'), ['code' => '111111']);
    }

    $this->actingAs($admin)
        ->post(route('two-factor.challenge.store'), ['code' => kodeSaatIni($rahasia)])
        ->assertSessionHasErrors('code');

    // Bahkan kode yang benar pun ditolak selama terkunci.
    expect(session(RequireTwoFactor::KUNCI_SESI))->toBeNull();
});

it('menyimpan rahasia dalam keadaan terenkripsi', function () {
    $rahasia = app(TwoFactor::class)->rahasiaBaru();
    $admin = adminDenganTwoFactor($rahasia);

    // Dump database tidak boleh setara dengan kunci ke seluruh akun staf.
    $mentah = DB::table('users')->where('id', $admin->id)->value('two_factor_secret');

    expect($mentah)->not->toBe($rahasia)
        ->and($admin->fresh()->two_factor_secret)->toBe($rahasia);
});

it('tidak mengaktifkan 2FA sebelum kode pendaftaran dikonfirmasi', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $komponen = Livewire::actingAs($admin)
        ->test(Keamanan::class)
        ->call('mulai');

    // Rahasia baru DISIMPAN setelah kodenya terbukti cocok — kalau tidak, orang
    // yang gagal memindai QR tetap terkunci oleh rahasia yang tidak pernah ada
    // di HP-nya.
    expect($admin->fresh()->two_factor_secret)->toBeNull();

    $komponen->set('kode', '000000')->call('konfirmasi');

    expect($admin->fresh()->twoFactorAktif())->toBeFalse();
});

it('mengaktifkan 2FA dan menerbitkan delapan kode pemulihan setelah kode benar', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $komponen = Livewire::actingAs($admin)->test(Keamanan::class)->call('mulai');

    $rahasia = $komponen->get('rahasiaBaru');

    $komponen->set('kode', kodeSaatIni($rahasia))->call('konfirmasi');

    expect($admin->fresh()->twoFactorAktif())->toBeTrue()
        ->and($admin->fresh()->two_factor_recovery_codes)->toHaveCount(8)
        ->and($komponen->get('kodePemulihanTampil'))->toHaveCount(8);
});

it('menuntut kata sandi untuk mematikan 2FA', function () {
    $rahasia = app(TwoFactor::class)->rahasiaBaru();
    $admin = adminDenganTwoFactor($rahasia);

    // Menurunkan keamanan akun tidak boleh bisa dilakukan orang yang cuma
    // menemukan layar ini terbuka di komputer yang ditinggal.
    Livewire::actingAs($admin)
        ->test(Keamanan::class)
        ->set('password', 'kata-sandi-ngawur')
        ->call('matikan');

    expect($admin->fresh()->twoFactorAktif())->toBeTrue();

    Livewire::actingAs($admin)
        ->test(Keamanan::class)
        ->set('password', 'password')
        ->call('matikan');

    expect($admin->fresh()->twoFactorAktif())->toBeFalse();
});

it('menahan panel vendor dengan aturan yang sama', function () {
    $rahasia = app(TwoFactor::class)->rahasiaBaru();

    $vendor = User::factory()->vendor()->create([
        'two_factor_secret' => $rahasia,
        'two_factor_recovery_codes' => [],
        'two_factor_confirmed_at' => now(),
    ]);

    $this->actingAs($vendor)->get('/vendor')->assertRedirect(route('two-factor.challenge'));
});

it('tidak menahan halaman tantangannya sendiri', function () {
    // Kalau ikut tertahan, ia mengarahkan ke dirinya sendiri tanpa henti.
    $rahasia = app(TwoFactor::class)->rahasiaBaru();

    $this->actingAs(adminDenganTwoFactor($rahasia))
        ->get(route('two-factor.challenge'))
        ->assertOk()
        ->assertSee('Masukkan kode dari aplikasi');
});

it('menutup halaman tantangan dari customer', function () {
    $this->actingAs(User::factory()->customer()->create())
        ->get(route('two-factor.challenge'))
        ->assertForbidden();
});
