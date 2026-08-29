<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Dump database ke berkas terkompresi, lalu buang dump yang lewat masa retensi.
 *
 * Ini backup MANDIRI, pelengkap backup otomatis Hostinger — bukan penggantinya.
 * Backup yang hanya ada di panel penyedia hosting punya satu titik gagal yang
 * sama dengan servernya: kalau akunnya bermasalah, backup-nya ikut tidak bisa
 * diambil. Dump di sini bisa disalin ke luar server.
 */
class BackupDatabase extends Command
{
    protected $signature = 'db:backup
        {--keep= : Berapa hari dump disimpan (default dari config backup.keep_days)}';

    protected $description = 'Dump database ke berkas .sql.gz dan hapus dump yang kedaluwarsa';

    public function handle(): int
    {
        $koneksi = config('database.default');

        if ($koneksi !== 'mysql') {
            $this->error("Perintah ini hanya untuk MySQL, koneksi aktif: {$koneksi}.");

            return self::FAILURE;
        }

        $tujuan = (string) config('backup.path');
        File::ensureDirectoryExists($tujuan);

        $berkas = $tujuan.DIRECTORY_SEPARATOR.sprintf(
            '%s-%s.sql',
            config('database.connections.mysql.database'),
            now()->format('Y-m-d_His'),
        );

        try {
            $this->dump($berkas);
        } catch (ProcessFailedException $e) {
            // Pesan mysqldump memuat host dan nama database, tapi TIDAK memuat
            // password: password dikirim lewat berkas kredensial sementara,
            // bukan argumen baris perintah.
            $this->error('mysqldump gagal: '.trim($e->getProcess()->getErrorOutput()));

            return self::FAILURE;
        }

        $terkompresi = $this->kompres($berkas);

        $this->info(sprintf(
            'Backup selesai: %s (%s KB)',
            basename($terkompresi),
            number_format(File::size($terkompresi) / 1024, 1),
        ));

        $this->buangKedaluwarsa($tujuan);

        return self::SUCCESS;
    }

    /**
     * Password TIDAK pernah masuk argumen perintah.
     *
     * Argumen proses terbaca semua pengguna di server lewat `ps aux` — di
     * shared hosting itu berarti terbaca tetangga. Karena itu kredensialnya
     * ditulis ke berkas sementara ber-permission 0600 yang dihapus di `finally`,
     * termasuk saat dump-nya gagal.
     */
    private function dump(string $berkas): void
    {
        $db = config('database.connections.mysql');

        $kredensial = tempnam(sys_get_temp_dir(), 'egoto-dump-');
        File::put($kredensial, sprintf(
            "[client]\nuser=%s\npassword=%s\nhost=%s\nport=%s\n",
            $db['username'],
            $db['password'],
            $db['host'],
            $db['port'],
        ));
        @chmod($kredensial, 0600);

        try {
            $proses = new Process([
                config('backup.mysqldump_binary'),
                '--defaults-extra-file='.$kredensial,
                '--single-transaction',
                '--quick',
                // Rutin dan trigger ikut: skema yang direstore setengah bukan
                // backup, itu jebakan yang baru ketahuan saat dipakai.
                '--routines',
                '--triggers',
                '--no-tablespaces',
                $db['database'],
            ]);

            $proses->setTimeout(600);
            $proses->mustRun();

            File::put($berkas, $proses->getOutput());
        } finally {
            File::delete($kredensial);
        }
    }

    private function kompres(string $berkas): string
    {
        $tujuan = $berkas.'.gz';

        $masuk = fopen($berkas, 'rb');
        $keluar = gzopen($tujuan, 'wb9');

        while (! feof($masuk)) {
            gzwrite($keluar, (string) fread($masuk, 262144));
        }

        fclose($masuk);
        gzclose($keluar);
        File::delete($berkas);

        return $tujuan;
    }

    /**
     * Retensi dihitung dari waktu ubah berkas, bukan dari nama — nama bisa
     * diubah orang, waktu ubah tidak berubah karena disalin ke tempat lain.
     */
    private function buangKedaluwarsa(string $tujuan): void
    {
        $hari = (int) ($this->option('keep') ?? config('backup.keep_days'));

        if ($hari < 1) {
            return;
        }

        $batas = now()->subDays($hari)->getTimestamp();
        $dibuang = 0;

        foreach (File::glob($tujuan.DIRECTORY_SEPARATOR.'*.sql.gz') as $lama) {
            if (File::lastModified($lama) < $batas) {
                File::delete($lama);
                $dibuang++;
            }
        }

        if ($dibuang > 0) {
            $this->line("{$dibuang} backup lewat {$hari} hari dibuang.");
        }
    }
}
