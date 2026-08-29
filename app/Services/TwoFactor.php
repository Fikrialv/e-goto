<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Verifikasi dua langkah berbasis TOTP (RFC 6238) untuk akun staf.
 *
 * Perhitungan kodenya diserahkan `pragmarx/google2fa` — pustaka yang sama yang
 * dipakai Laravel Fortify di baliknya. Yang ditulis di sini hanya perekatnya:
 * pendaftaran, kode pemulihan, dan verifikasi. Fortify penuh sengaja tidak
 * dipasang karena ia membawa route, view, dan tumpukan autentikasi keduanya
 * yang akan berdampingan dengan alur `/masuk` yang sudah ada sejak D3.
 */
class TwoFactor
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function rahasiaBaru(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Delapan kode sekali pakai untuk keadaan HP hilang atau aplikasi
     * authenticator terhapus. Tanpa ini, kehilangan HP berarti kehilangan akses
     * permanen ke panel — dan pemulihannya harus lewat akses database.
     *
     * @return array<int, string>
     */
    public function kodePemulihan(): array
    {
        return collect(range(1, 8))
            ->map(fn (): string => Str::upper(Str::random(5).'-'.Str::random(5)))
            ->all();
    }

    /**
     * URI otpauth:// yang dipindai aplikasi authenticator.
     *
     * Label memakai email supaya satu aplikasi bisa memegang beberapa akun
     * E-GOTO tanpa tertukar.
     */
    public function uriPendaftaran(User $user, string $rahasia): string
    {
        return $this->google2fa->getQRCodeUrl(
            (string) config('app.name'),
            $user->email,
            $rahasia,
        );
    }

    /** QR sebagai SVG inline — tanpa request ke layanan luar mana pun. */
    public function qrSvg(string $uri): string
    {
        $writer = new Writer(new ImageRenderer(
            new RendererStyle(220, 0),
            new SvgImageBackEnd,
        ));

        return $writer->writeString($uri);
    }

    /**
     * Jendela toleransi 1 satuan (±30 detik) untuk jam HP yang meleset sedikit.
     * Lebih lebar dari itu memperpanjang umur kode yang sudah bocor.
     */
    public function kodeSah(string $rahasia, string $kode): bool
    {
        return $this->google2fa->verifyKey($rahasia, trim($kode), 1);
    }

    /**
     * Kode pemulihan dipakai SEKALI: yang cocok langsung dibuang dari daftar.
     * Kode yang tetap berlaku setelah dipakai sama saja dengan kata sandi kedua
     * yang tercetak di kertas.
     */
    public function pakaiKodePemulihan(User $user, string $kode): bool
    {
        $tersisa = $user->two_factor_recovery_codes ?? [];
        $kode = Str::upper(trim($kode));

        if (! in_array($kode, $tersisa, true)) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => array_values(array_diff($tersisa, [$kode])),
        ])->save();

        return true;
    }
}
