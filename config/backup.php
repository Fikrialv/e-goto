<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lokasi dump
    |--------------------------------------------------------------------------
    |
    | Default `storage/app/backups` — DI LUAR repo (storage/ sudah di
    | .gitignore) dan di luar `public/`, jadi tidak bisa diunduh siapa pun yang
    | menebak URL. Dump database memuat seluruh isi tabel, termasuk hash
    | password dan kolom NIK terenkripsi; satu berkas bocor sama saja dengan
    | seluruh database bocor.
    |
    | Di Hostinger, arahkan ke folder di luar `public_html` dan salin keluar
    | server secara berkala — backup yang hanya ada di mesin yang sama tidak
    | menolong saat mesin itulah yang bermasalah.
    |
    */

    'path' => env('BACKUP_PATH', storage_path('app/backups')),

    /*
    |--------------------------------------------------------------------------
    | Retensi
    |--------------------------------------------------------------------------
    |
    | Dump lebih tua dari ini dibuang tiap kali `db:backup` jalan. 14 hari
    | dipilih supaya kerusakan yang baru ketahuan seminggu kemudian masih punya
    | titik balik yang bersih, tanpa menumpuk berkas tanpa batas di kuota
    | shared hosting yang terbatas.
    |
    */

    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Lokasi mysqldump
    |--------------------------------------------------------------------------
    |
    | Di XAMPP Windows biasanya `C:\xampp\mysql\bin\mysqldump.exe`; di Hostinger
    | `mysqldump` sudah ada di PATH.
    |
    */

    'mysqldump_binary' => env('MYSQLDUMP_BINARY', 'mysqldump'),

];
