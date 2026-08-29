<?php

use App\Enums\UserRole;
use App\Filament\InisialAvatarProvider;
use App\Models\User;

/**
 * Header keamanan (2026-08-29).
 *
 * Yang dijaga di sini bukan "header ada", melainkan tiga keputusan yang mahal
 * kalau terbalik: HSTS tidak boleh bocor ke koneksi HTTP, CSP tidak boleh
 * menegakkan diri sendiri sebelum dinyalakan sadar, dan versi PHP tidak boleh
 * ikut terkirim.
 */
it('memasang header keamanan di halaman publik', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    expect($response->headers->get('Permissions-Policy'))->toContain('camera=()');
});

it('tidak membocorkan versi PHP lewat X-Powered-By', function () {
    expect($this->get('/')->headers->has('X-Powered-By'))->toBeFalse();
});

it('tidak mengirim HSTS di koneksi HTTP biasa', function () {
    // Terkirim di localhost, browser mengunci host itu ke HTTPS selama
    // max-age — dan penguncian itu tidak bisa dibatalkan dari sisi server.
    expect($this->get('/')->headers->has('Strict-Transport-Security'))->toBeFalse();
});

it('mengirim HSTS hanya lewat HTTPS', function () {
    $response = $this->get('https://localhost/');

    expect($response->headers->get('Strict-Transport-Security'))
        ->toContain('max-age=31536000')
        // preload itu satu arah: keluar dari daftar browser butuh berbulan-bulan.
        ->not->toContain('preload');
});

it('memakai mode Report-Only selama penegakan CSP belum dinyalakan', function () {
    config()->set('security.csp_enforce', false);

    $response = $this->get('/');

    expect($response->headers->has('Content-Security-Policy-Report-Only'))->toBeTrue()
        ->and($response->headers->has('Content-Security-Policy'))->toBeFalse();
});

it('menegakkan CSP saat config dinyalakan', function () {
    config()->set('security.csp_enforce', true);

    expect($this->get('/')->headers->has('Content-Security-Policy'))->toBeTrue();
});

it('menutup sumber skrip luar dan clickjacking lewat CSP', function () {
    // Mode ditetapkan eksplisit: test tidak boleh berubah hasilnya cuma karena
    // .env di mesin developer menyalakan penegakan CSP.
    config()->set('security.csp_enforce', false);

    $csp = $this->get('/')->headers->get('Content-Security-Policy-Report-Only');

    // Inti perlindungannya: satu XSS tersimpan tetap tidak bisa memuat payload
    // dari server penyerang.
    expect($csp)->toContain("default-src 'self'")
        ->and($csp)->toContain("object-src 'none'")
        ->and($csp)->toContain("frame-ancestors 'self'")
        ->and($csp)->toContain("form-action 'self'");
});

it('mengizinkan CDN font panel hanya untuk style dan font, bukan default-src', function () {
    config()->set('security.csp_enforce', false);

    $csp = $this->get('/')->headers->get('Content-Security-Policy-Report-Only');

    // Panel Filament memuat fontnya dari bunny.net; memblokirnya membuat panel
    // jatuh ke font sistem tanpa peringatan apa pun. Izinnya dibatasi dua
    // direktif, tidak dinaikkan ke default-src.
    expect($csp)->toContain("style-src 'self' 'unsafe-inline' https://fonts.bunny.net")
        ->and($csp)->toContain("font-src 'self' data: https://fonts.bunny.net")
        ->and($csp)->toContain("default-src 'self';")
        ->and($csp)->not->toContain("default-src 'self' https://fonts.bunny.net");
});

it('tidak memuat avatar panel dari domain luar', function () {
    // Bawaan Filament menembak ui-avatars.com tiap muat halaman — satu request
    // luar untuk dua huruf, dan diblokir begitu img-src ditegakkan.
    $admin = User::factory()->create(['role' => UserRole::Admin, 'name' => 'Admin E-GOTO']);

    $avatar = app(InisialAvatarProvider::class)->get($admin);

    expect($avatar)->toStartWith('data:image/svg+xml')
        ->and($avatar)->not->toContain('ui-avatars.com')
        ->and(rawurldecode($avatar))->toContain('>AE<');
});

it('memasang header yang sama di panel admin', function () {
    // Panel Filament punya tumpukan middleware sendiri; kalau header global
    // tidak ikut sampai ke sana, justru layar paling sensitif yang telanjang.
    $this->get('/admin/login')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});
