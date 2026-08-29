<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan untuk seluruh respons, dipasang global di bootstrap/app.php
 * — bukan per route. Header yang dipasang per route selalu punya satu route
 * yang terlupa, dan yang terlupa itulah yang dipakai penyerang.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        /*
         * Versi PHP persis adalah hadiah gratis buat siapa pun yang memindai
         * target: ia memetakan langsung ke daftar CVE yang tinggal dicoba.
         *
         * Dua panggilan, bukan satu, karena headernya bisa datang dari dua
         * tempat. `expose_php=On` membuat PHP menambahkannya sendiri di level
         * SAPI, di luar jangkauan HeaderBag Symfony — itu hanya bisa dicabut
         * `header_remove()`. Membuangnya di satu tempat saja terlihat berhasil
         * di test (di sana PHP tidak menambahkannya) sambil tetap bocor di
         * server sungguhan.
         */
        $response->headers->remove('X-Powered-By');

        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        /*
         * Batasi API perangkat yang tidak dipakai project ini sama sekali.
         * Kamera memang dipakai untuk memindai QR check-in, tapi itu lewat
         * aplikasi kamera bawaan HP, bukan getUserMedia di halaman kita.
         */
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
        );

        /*
         * HSTS hanya di koneksi yang memang sudah HTTPS. Kalau ikut terkirim
         * dari http://127.0.0.1, browser mengunci host itu ke HTTPS selama
         * max-age dan dev lokal jadi tidak bisa dibuka — dan penguncian itu
         * tidak bisa dibatalkan dari sisi server.
         *
         * `preload` sengaja TIDAK dipasang: masuk daftar preload browser itu
         * satu arah dan butuh berbulan-bulan untuk keluar.
         */
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
            );
        }

        $response->headers->set($this->namaHeaderCsp(), $this->csp());

        return $response;
    }

    /**
     * Report-Only selama `SECURITY_CSP_ENFORCE` belum dinyalakan.
     *
     * CSP yang ditegakkan sebelum diuji per halaman akan mematikan panel
     * Filament tanpa suara — pelanggarannya muncul di console browser, bukan
     * sebagai error 500 yang ketahuan di log. Jadi urutannya: amati dulu,
     * tegakkan setelah bersih.
     */
    private function namaHeaderCsp(): string
    {
        return config('security.csp_enforce')
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';
    }

    private function csp(): string
    {
        /*
         * 'unsafe-eval' dibutuhkan Alpine.js: ekspresi `x-data`/`x-show`
         * dievaluasi saat runtime lewat konstruktor Function. Livewire dan
         * Filament berdiri di atas Alpine, jadi menghapusnya berarti mematikan
         * seluruh panel admin — bukan pilihan yang bisa diambil sekarang.
         *
         * 'unsafe-inline' dibutuhkan atribut Alpine dan style inline Filament.
         *
         * Yang tetap tertutup rapat, dan ini bagian yang benar-benar berharga:
         * default-src 'self' menolak skrip dari domain luar mana pun, jadi
         * satu XSS tersimpan tidak bisa memuat payload dari server penyerang.
         * frame-ancestors dan form-action menutup clickjacking dan pengalihan
         * kiriman form.
         */
        /*
         * fonts.bunny.net diizinkan HANYA untuk stylesheet dan berkas font.
         * Panel Filament memuat fontnya dari sana; memblokirnya membuat panel
         * jatuh ke font sistem tanpa peringatan apa pun. Bunny sengaja dipilih
         * Filament sebagai pengganti Google Fonts yang tidak mencatat pengguna,
         * tapi ia tetap domain luar — jadi izinnya dibatasi dua direktif ini,
         * bukan dinaikkan ke `default-src`.
         *
         * Sisi customer tidak menyentuhnya sama sekali: fontnya dibundel lokal
         * lewat @fontsource.
         */
        $fontCdn = 'https://fonts.bunny.net';

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' {$fontCdn}",
            "img-src 'self' data: blob:",
            "font-src 'self' data: {$fontCdn}",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
    }
}
