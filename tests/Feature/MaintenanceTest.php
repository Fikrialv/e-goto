<?php

use Illuminate\Support\Facades\File;

/**
 * Perawatan mandiri (2026-08-29): halaman maintenance bermerek, backup
 * database, rotasi log, dan health check.
 */
it('merender halaman maintenance tanpa bergantung pada Vite atau database', function () {
    $html = view('errors.503')->render();

    // Halaman ini justru harus tampil saat aplikasinya tidak bisa diandalkan.
    // Kalau ia memanggil manifest Vite, ia ikut gagal tepat saat dibutuhkan.
    expect($html)->not->toContain('/build/')
        ->and($html)->toContain('/images/logo2.svg')
        ->and($html)->toContain('Sebentar ya')
        // Orang yang sudah transfer tapi belum diverifikasi adalah yang paling
        // panik saat situs mati — pertanyaannya dijawab di halaman itu sendiri.
        ->and($html)->toContain('Tidak perlu bayar ulang')
        ->and($html)->toContain('wa.me/'.config('booking.admin_whatsapp'));
});

it('memakai latar putih dan tidak memakai gradient di halaman maintenance', function () {
    $html = view('errors.503')->render();

    expect($html)->toContain('#ffffff')
        ->and($html)->not->toContain('gradient');
});

it('menyediakan health check bawaan di /up', function () {
    $this->get('/up')->assertOk();
});

it('membuat dump terkompresi di folder di luar public', function () {
    $tujuan = storage_path('app/backups-test');
    File::deleteDirectory($tujuan);
    config()->set('backup.path', $tujuan);

    $this->artisan('db:backup')->assertSuccessful();

    $dump = File::glob($tujuan.DIRECTORY_SEPARATOR.'*.sql.gz');

    expect($dump)->toHaveCount(1)
        ->and(gzdecode(File::get($dump[0])))->toContain('CREATE TABLE');

    // Dump memuat seluruh isi tabel — hash password dan kolom NIK terenkripsi
    // ikut di dalamnya. Satu berkas yang bisa diunduh dari URL sama saja
    // dengan seluruh database bocor.
    expect($tujuan)->not->toContain(public_path());

    File::deleteDirectory($tujuan);
});

it('membuang dump yang lewat masa retensi', function () {
    $tujuan = storage_path('app/backups-test');
    File::deleteDirectory($tujuan);
    File::ensureDirectoryExists($tujuan);
    config()->set('backup.path', $tujuan);

    $lama = $tujuan.DIRECTORY_SEPARATOR.'egoto-lama.sql.gz';
    File::put($lama, gzencode('dump lama'));
    touch($lama, now()->subDays(30)->getTimestamp());

    $this->artisan('db:backup', ['--keep' => 14])->assertSuccessful();

    expect(File::exists($lama))->toBeFalse()
        // Dump baru dari perintah yang sama tidak boleh ikut terbuang.
        ->and(File::glob($tujuan.DIRECTORY_SEPARATOR.'*.sql.gz'))->toHaveCount(1);

    File::deleteDirectory($tujuan);
});

it('menyimpan retensi nol berarti tidak membuang apa pun', function () {
    $tujuan = storage_path('app/backups-test');
    File::deleteDirectory($tujuan);
    File::ensureDirectoryExists($tujuan);
    config()->set('backup.path', $tujuan);

    $lama = $tujuan.DIRECTORY_SEPARATOR.'egoto-lama.sql.gz';
    File::put($lama, gzencode('dump lama'));
    touch($lama, now()->subYears(2)->getTimestamp());

    $this->artisan('db:backup', ['--keep' => 0])->assertSuccessful();

    expect(File::exists($lama))->toBeTrue();

    File::deleteDirectory($tujuan);
});

it('merotasi log harian, bukan satu berkas tanpa batas', function () {
    // Tanpa LOG_STACK=daily, stack jatuh ke `single` dan laravel.log tumbuh
    // sampai kuota shared hosting habis.
    expect(config('logging.channels.stack.channels'))->toContain('daily')
        ->and(config('logging.channels.daily.days'))->toBe(14);
});
