<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Satu-satunya pekerjaan periodik V1. Cukup dipicu satu cron di server
 * (`php artisan schedule:run` tiap menit) — tidak ada queue worker, sesuai
 * batasan shared hosting (PLAN.md §12b).
 *
 * withoutOverlapping: kalau sapuan sebelumnya belum selesai (misal ada lonjakan
 * booking kedaluwarsa), jalankan berikutnya menunggu — bukan menumpuk dan
 * memperebutkan baris jadwal yang sama.
 */
Schedule::command('bookings:expire')->everyFiveMinutes()->withoutOverlapping();
