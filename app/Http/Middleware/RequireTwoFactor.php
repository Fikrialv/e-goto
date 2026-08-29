<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menahan panel staf sampai kode verifikasi dua langkah dimasukkan.
 *
 * Dipasang di `authMiddleware` panel Filament, jadi ia berjalan SETELAH
 * `Authenticate` — user pasti sudah dikenali saat sampai sini.
 *
 * Tandanya disimpan di sesi, bukan di cookie terpisah: sesi sudah diputar
 * ulang saat login (`AuthenticateSession`), jadi tanda ini ikut hangus setiap
 * kali orangnya masuk lagi. Cookie "ingat perangkat ini" sengaja tidak dibuat
 * — ia memindahkan keputusan keamanan ke perangkat yang bisa hilang.
 */
class RequireTwoFactor
{
    public const KUNCI_SESI = 'two_factor_terverifikasi';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Belum menyalakan 2FA berarti tidak ada yang perlu ditanyakan. Ini
        // yang membuat fiturnya opsional: memaksakannya ke seluruh akun staf
        // sekaligus akan mengunci pemilik project keluar begitu migration
        // jalan.
        if ($user === null || ! $user->twoFactorAktif()) {
            return $next($request);
        }

        if ($request->session()->get(self::KUNCI_SESI) === $user->getAuthIdentifier()) {
            return $next($request);
        }

        $tantangan = route('two-factor.challenge');

        // Halaman tantangannya sendiri harus lolos, kalau tidak ia mengarahkan
        // ke dirinya sendiri tanpa henti.
        if ($request->routeIs('two-factor.*') || $request->fullUrlIs($tantangan)) {
            return $next($request);
        }

        return redirect()->guest($tantangan);
    }
}
