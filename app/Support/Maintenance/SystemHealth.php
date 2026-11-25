<?php

namespace App\Support\Maintenance;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pemeriksaan kesehatan server yang bisa dibaca orang awam: tiap baris punya status dan saran tindakan.
 * Status: ok (hijau), warn (perlu perhatian), fail (harus diperbaiki).
 */
class SystemHealth
{
    public const EXTENSIONS = ['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'fileinfo', 'gd', 'intl', 'zlib', 'sodium'];

    /** @return array<int,array{label:string,status:string,detail:string}> */
    public static function checks(): array
    {
        $c = [];
        $add = function (string $label, string $status, string $detail) use (&$c) {
            $c[] = compact('label', 'status', 'detail');
        };

        $add('Versi PHP', version_compare(PHP_VERSION, '8.3.0', '>=') ? 'ok' : 'fail',
            PHP_VERSION.(version_compare(PHP_VERSION, '8.3.0', '>=') ? '' : '. Wajib 8.3 atau lebih baru (cPanel > MultiPHP Manager).'));

        $missing = array_values(array_filter(self::EXTENSIONS, fn ($e) => ! extension_loaded($e)));
        $add('Ekstensi PHP', $missing ? 'fail' : 'ok', $missing
            ? 'Belum aktif: '.implode(', ', $missing).'. Aktifkan di cPanel > Select PHP Version > Extensions.'
            : 'Semua ekstensi yang dibutuhkan aktif.');

        try {
            $ver = DB::connection()->getDriverName() === 'sqlite'
                ? 'SQLite '.DB::scalar('select sqlite_version()')
                : (string) DB::scalar('select version()');
            $add('Database', 'ok', 'Tersambung ('.$ver.').');
        } catch (Throwable $e) {
            $add('Database', 'fail', 'Tidak tersambung. Periksa DB_DATABASE, DB_USERNAME, DB_PASSWORD di file .env.');
        }

        $pending = Housekeeping::pendingMigrations();
        $add('Migrasi database', $pending ? 'warn' : 'ok', $pending
            ? count($pending).' pembaruan struktur belum dijalankan. Tekan "Jalankan migrasi".'
            : 'Struktur database sudah terbaru.');

        $prod = app()->environment('production');
        $add('Mode aplikasi', $prod ? 'ok' : 'warn', $prod ? 'production' : 'APP_ENV='.app()->environment().'. Di server harus production.');
        $add('Mode debug', config('app.debug') ? ($prod ? 'fail' : 'warn') : 'ok', config('app.debug')
            ? 'APP_DEBUG=true membocorkan detail teknis saat error. Ubah ke false di .env.'
            : 'Mati (aman).');

        $https = str_starts_with((string) config('app.url'), 'https://');
        $add('Alamat situs (APP_URL)', $https ? 'ok' : 'warn', config('app.url').($https ? '' : '. Gunakan https:// di server.'));

        foreach (['storage' => storage_path(), 'bootstrap/cache' => base_path('bootstrap/cache')] as $name => $dir) {
            $add("Folder {$name} bisa ditulisi", is_writable($dir) ? 'ok' : 'fail', is_writable($dir) ? 'Ya.' : 'Tidak. Ubah izin folder menjadi 755 lewat File Manager.');
        }

        $root = config('filesystems.disks.public.root');
        if ($root === public_path('storage')) {
            $ok = is_dir($root) && is_writable($root);
            $add('Folder gambar unggahan', $ok ? 'ok' : 'fail', $ok
                ? 'public_html/storage ada dan bisa ditulisi.'
                : 'public_html/storage belum ada atau tidak bisa ditulisi. Buat foldernya, izin 755.');
        } else {
            $link = public_path('storage');
            $linked = is_dir($link) && (is_link($link) || realpath($link) === realpath($root));
            $add('Tautan folder gambar (storage)', $linked ? 'ok' : 'fail', $linked
                ? 'Gambar unggahan bisa diakses publik.'
                : 'Belum ada. Tekan "Buat tautan storage" di bawah.');
        }

        $down = MaintenanceMode::active();
        $add('Mode perbaikan', $down ? 'warn' : 'ok', $down ? 'Sedang AKTIF: pengunjung melihat halaman perbaikan.' : 'Tidak aktif.');

        $last = DatabaseBackup::list()[0] ?? null;
        $age = $last ? (int) floor((time() - $last['time']) / 86400) : null;
        $add('Cadangan database terakhir', $last === null ? 'warn' : ($age > 7 ? 'warn' : 'ok'), $last === null
            ? 'Belum pernah dibuat.'
            : \Carbon\Carbon::createFromTimestamp($last['time'], 'Asia/Makassar')->translatedFormat('j M Y, H.i').' ('.($age === 0 ? 'hari ini' : $age.' hari lalu').').');

        $log = Housekeeping::currentLog();
        $size = $log ? filesize($log) : 0;
        $add('Ukuran log error', $size > 5 * 1024 * 1024 ? 'warn' : 'ok', Housekeeping::bytes($size).($size > 5 * 1024 * 1024 ? '. Tekan "Rapikan" untuk memangkasnya.' : ''));

        $free = @disk_free_space(storage_path());
        if ($free !== false) {
            $add('Ruang disk tersisa', $free < 200 * 1024 * 1024 ? 'warn' : 'ok', Housekeeping::bytes((int) $free).' (menurut server; kuota cPanel bisa lebih kecil).');
        }

        $add('Optimasi konfigurasi', app()->configurationIsCached() ? 'ok' : 'warn', app()->configurationIsCached()
            ? 'Konfigurasi dan rute sudah di-cache (lebih cepat).'
            : 'Belum di-cache. Tekan "Optimalkan" setelah selesai mengubah .env.');

        return $c;
    }

    /** @return array{ok:int,warn:int,fail:int} */
    public static function summary(array $checks): array
    {
        $s = ['ok' => 0, 'warn' => 0, 'fail' => 0];
        foreach ($checks as $c) {
            $s[$c['status']]++;
        }

        return $s;
    }
}
