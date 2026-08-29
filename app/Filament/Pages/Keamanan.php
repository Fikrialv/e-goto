<?php

namespace App\Filament\Pages;

use App\Services\TwoFactor;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;

/**
 * Pendaftaran verifikasi dua langkah untuk akun staf yang sedang masuk.
 *
 * TIDAK dibatasi AdminScope: tiap admin mengurus keamanan akunnya sendiri, dan
 * layar yang membiarkan satu admin menyalakan/mematikan 2FA admin lain justru
 * kebalikan dari yang ingin dicapai.
 *
 * Akun admin memegang tombol approve pembayaran dan penerbitan tiket — satu
 * kata sandi yang bocor cukup untuk menyetujui pembayaran yang tidak pernah
 * masuk. Itu alasan fitur ini ada di sini, bukan sekadar kelengkapan.
 */
class Keamanan extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'Keamanan Akun';

    protected static ?string $title = 'Keamanan akun';

    protected static ?string $navigationGroup = 'Akun';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.keamanan';

    public string $kode = '';

    public string $password = '';

    /** Rahasia yang sedang didaftarkan, belum berlaku sampai dikonfirmasi. */
    public ?string $rahasiaBaru = null;

    /** @var array<int, string> */
    public array $kodePemulihanTampil = [];

    public function mount(): void
    {
        // Pendaftaran yang ditinggalkan setengah jalan tidak boleh menyisakan
        // rahasia menggantung di baris user.
        $this->rahasiaBaru = null;
    }

    public function mulai(TwoFactor $twoFactor): void
    {
        $this->rahasiaBaru = $twoFactor->rahasiaBaru();
        $this->kode = '';
    }

    public function batal(): void
    {
        $this->rahasiaBaru = null;
        $this->kode = '';
    }

    public function konfirmasi(TwoFactor $twoFactor): void
    {
        $this->validate(['kode' => ['required', 'string']], attributes: ['kode' => 'kode verifikasi']);

        if ($this->rahasiaBaru === null) {
            return;
        }

        /*
         * Rahasianya baru DISIMPAN setelah kodenya terbukti cocok. Menyimpannya
         * lebih dulu berarti orang yang gagal memindai QR tetap terkunci oleh
         * rahasia yang tidak pernah ada di HP-nya.
         */
        if (! $twoFactor->kodeSah($this->rahasiaBaru, $this->kode)) {
            Notification::make()
                ->title('Kode tidak cocok')
                ->body('Periksa lagi kode enam digit di aplikasi authenticator kamu.')
                ->danger()
                ->send();

            return;
        }

        $pemulihan = $twoFactor->kodePemulihan();

        auth()->user()->forceFill([
            'two_factor_secret' => $this->rahasiaBaru,
            'two_factor_recovery_codes' => $pemulihan,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->rahasiaBaru = null;
        $this->kode = '';

        // Ditampilkan sekali di layar ini saja. Menyimpannya untuk ditampilkan
        // ulang kapan pun berarti daftar kode masuk cadangan tergeletak di
        // halaman yang cuma butuh sesi aktif untuk dibuka.
        $this->kodePemulihanTampil = $pemulihan;

        Notification::make()
            ->title('Verifikasi dua langkah aktif')
            ->body('Simpan kode pemulihan di bawah sekarang — ia tidak ditampilkan lagi.')
            ->success()
            ->send();
    }

    public function matikan(): void
    {
        $this->validate(['password' => ['required', 'string']], attributes: ['password' => 'kata sandi']);

        /*
         * Kata sandi diminta lagi di sini. Mematikan 2FA adalah menurunkan
         * keamanan akun, jadi ia tidak boleh bisa dilakukan orang yang cuma
         * menemukan layar ini terbuka di komputer yang ditinggal.
         */
        if (! Hash::check($this->password, auth()->user()->password)) {
            Notification::make()->title('Kata sandi salah')->danger()->send();
            $this->password = '';

            return;
        }

        auth()->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->password = '';
        $this->kodePemulihanTampil = [];

        Notification::make()->title('Verifikasi dua langkah dimatikan')->warning()->send();
    }

    public function getAktifProperty(): bool
    {
        return auth()->user()->twoFactorAktif();
    }

    public function getQrProperty(): ?string
    {
        if ($this->rahasiaBaru === null) {
            return null;
        }

        $twoFactor = app(TwoFactor::class);

        return $twoFactor->qrSvg($twoFactor->uriPendaftaran(auth()->user(), $this->rahasiaBaru));
    }

    public function getSisaKodePemulihanProperty(): int
    {
        return count(auth()->user()->two_factor_recovery_codes ?? []);
    }
}
