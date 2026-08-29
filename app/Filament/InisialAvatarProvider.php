<?php

namespace App\Filament;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Avatar inisial yang dirender sendiri sebagai SVG data URI.
 *
 * Menggantikan `UiAvatarsProvider` bawaan Filament, yang menembak
 * `https://ui-avatars.com/api/?name=...` setiap kali halaman panel dimuat.
 * Tiga alasan, dan yang ketiga yang menentukan:
 *
 * 1. Satu request ke domain luar di tiap halaman panel, untuk gambar yang
 *    isinya cuma dua huruf — melawan aturan performa `CLAUDE.md` §9.
 * 2. Inisial staf dan alamat IP server/pengguna ikut terkirim ke pihak ketiga
 *    yang tidak pernah dicantumkan di `/kebijakan-privasi`.
 * 3. `Content-Security-Policy` project ini memakai `img-src 'self' data: blob:`.
 *    Begitu CSP ditegakkan, avatarnya diblokir dan tidak pernah tampil — dan
 *    kegagalannya senyap, cuma muncul di console browser.
 *
 * Warnanya teal brand, sama dengan `primary` panel, jadi ia tidak terbaca
 * sebagai gambar yang gagal dimuat.
 */
class InisialAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $inisial = Str::of(Filament::getNameForDefaultAvatar($record))
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $bagian): string => Str::upper(mb_substr($bagian, 0, 1)))
            ->join('');

        $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="img">
                <rect width="64" height="64" fill="#077C82"/>
                <text x="32" y="32" fill="#F4F8F8" font-family="system-ui, sans-serif"
                      font-size="26" font-weight="600" text-anchor="middle"
                      dominant-baseline="central">{$inisial}</text>
            </svg>
            SVG;

        // rawurlencode, bukan base64: hasilnya lebih pendek untuk SVG dan tetap
        // aman dipakai di atribut src.
        return 'data:image/svg+xml;charset=UTF-8,'.rawurlencode(preg_replace('/\s+/', ' ', $svg));
    }
}
