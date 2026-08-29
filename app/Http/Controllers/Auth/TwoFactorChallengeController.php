<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequireTwoFactor;
use App\Services\TwoFactor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Layar kode verifikasi dua langkah, ditampilkan tepat setelah kata sandi
 * benar tapi sebelum panel terbuka.
 *
 * Sengaja route web biasa, bukan halaman Filament: kalau ia hidup di dalam
 * panel, ia ikut tertahan middleware panel yang justru sedang menahan
 * orangnya — dan hasilnya pengalihan tanpa henti.
 */
class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->twoFactorAktif()) {
            return redirect()->intended('/');
        }

        return view('pages.two-factor-challenge');
    }

    public function store(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->twoFactorAktif(), 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        /*
         * Enam digit hanya punya satu juta kemungkinan dan berlaku 30 detik —
         * tanpa pembatasan laju, menebaknya jadi pekerjaan yang bisa
         * diselesaikan skrip. Dikunci per user, bukan per IP: penyerang bisa
         * berganti IP, tidak bisa berganti akun sasaran.
         */
        $kunci = 'two-factor:'.$user->getAuthIdentifier();

        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            throw ValidationException::withMessages([
                'code' => 'Terlalu banyak percobaan. Coba lagi dalam '
                    .ceil(RateLimiter::availableIn($kunci) / 60).' menit.',
            ]);
        }

        $sah = $twoFactor->kodeSah($user->two_factor_secret, $data['code'])
            || $twoFactor->pakaiKodePemulihan($user, $data['code']);

        if (! $sah) {
            RateLimiter::hit($kunci, 300);

            throw ValidationException::withMessages([
                'code' => 'Kode tidak cocok. Periksa lagi aplikasi authenticator kamu.',
            ]);
        }

        RateLimiter::clear($kunci);

        /*
         * Sesi diputar ulang setelah kode benar. Kalau tidak, id sesi yang
         * sudah dipegang penyerang sejak sebelum tantangan ini ikut naik
         * derajat menjadi sesi yang lolos dua langkah.
         */
        $request->session()->regenerate();
        $request->session()->put(RequireTwoFactor::KUNCI_SESI, $user->getAuthIdentifier());

        return redirect()->intended(filament()->getDefaultPanel()->getUrl());
    }
}
