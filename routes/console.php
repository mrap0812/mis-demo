<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Jadwal otomatis. Aktif bila cPanel > Cron Jobs menjalankan perintah ini setiap 5 menit:
 *   cd /home/AKUN/mis-app && php artisan schedule:run >/dev/null 2>&1
 * Tanpa cron situs tetap berjalan normal; cadangan bisa dibuat manual dari panel Pemeliharaan.
 */
use Illuminate\Support\Facades\Schedule;

Schedule::command('mis:backup')->dailyAt('02:30')->timezone('Asia/Makassar')->withoutOverlapping();
Schedule::call(fn () => \App\Support\Maintenance\Housekeeping::tidy())->weeklyOn(1, '03:00')->timezone('Asia/Makassar')->name('mis:tidy')->withoutOverlapping();
