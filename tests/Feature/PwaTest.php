<?php

/**
 * PWA installable (D7.6 a, test §9 #15).
 *
 * Yang dijaga di sini bukan "bisa dipasang" (itu urusan Lighthouse di browser),
 * melainkan batas cache-nya: service worker tidak boleh menyentuh dokumen HTML.
 * Halaman pembayaran yang tersaji dari cache berarti kuota, hitung mundur, dan
 * status verifikasi yang basi — jauh lebih berbahaya daripada halaman yang
 * tidak bisa dibuka offline.
 */
it('menyediakan manifest dengan ikon 192 dan 512', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.json')), true);

    expect($manifest['display'])->toBe('standalone')
        ->and($manifest['theme_color'])->toBe('#077C82')
        ->and($manifest['background_color'])->toBe('#F6FAFA')
        ->and($manifest['start_url'])->toBe('/')
        ->and(collect($manifest['icons'])->pluck('sizes')->all())->toBe(['192x192', '512x512']);

    foreach ($manifest['icons'] as $ikon) {
        expect(file_exists(public_path(ltrim($ikon['src'], '/'))))->toBeTrue();
    }
});

it('menautkan manifest dari layout customer', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('rel="manifest"', escape: false);
});

it('tidak pernah menyajikan dokumen dari cache service worker', function () {
    $sw = file_get_contents(public_path('sw.js'));

    // Navigasi dibiarkan lewat jaringan, dan hanya dua prefiks aset yang boleh
    // masuk cache. Kalau suatu saat ada yang melonggarkan ini, test ini gagal.
    expect($sw)->toContain("event.request.mode === 'navigate'")
        ->and($sw)->toContain("url.pathname.startsWith('/build/')")
        ->and($sw)->not->toContain('/booking')
        ->and($sw)->not->toContain('/bayar')
        ->and($sw)->not->toContain('/tiket');
});

it('halaman pembayaran tidak boleh disimpan cache bersama', function () {
    $booking = bookingSiapBayar();

    $this->actingAs($booking->user)->post(route('payments.confirm', $booking));

    $respons = $this->actingAs($booking->user)
        ->get(route('payments.show', $booking))
        ->assertOk()
        ->assertSee('Rp'.number_format($booking->total_amount, 0, ',', '.'));

    // Sesi login membuat Laravel menandai respons ini privat. Kalau suatu saat
    // ada middleware cache yang melonggarkannya, nominal unik milik satu orang
    // bisa tersaji ke pemesan lain.
    expect($respons->headers->get('cache-control'))->toContain('private');
});
