<?php

namespace App\Support\Maintenance;

use FilesystemIterator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/** Pekerjaan rumah tangga server: migrasi tertunda, ukuran folder, pembersihan, potongan log. */
class Housekeeping
{
    /** @return array<int,string> nama migrasi yang belum dijalankan */
    public static function pendingMigrations(): array
    {
        try {
            $m = app('migrator');
            $files = $m->getMigrationFiles(array_merge([database_path('migrations')], $m->paths()));
            $ran = $m->repositoryExists() ? $m->getRepository()->getRan() : [];

            return array_values(array_diff(array_keys($files), $ran));
        } catch (Throwable) {
            return [];
        }
    }

    /** Jalankan migrasi yang tertunda. Mengembalikan keluaran perintah (tanpa kode warna). */
    public static function migrate(): string
    {
        Artisan::call('migrate', ['--force' => true]);

        return trim(preg_replace('/\e\[[\d;]*m/', '', Artisan::output()));
    }

    /** @return array<int,array{label:string,path:string,size:int,files:int}> */
    public static function folderSizes(): array
    {
        $dirs = [
            'Gambar & berkas unggahan' => config('filesystems.disks.public.root'),
            'Cadangan database' => DatabaseBackup::dir(),
            'Log' => storage_path('logs'),
            'Cache & sesi sementara' => storage_path('framework'),
            'Aset situs (CSS, JS, font, gambar)' => public_path(),
        ];
        $out = [];
        foreach ($dirs as $label => $dir) {
            [$size, $files] = self::dirSize($dir);
            $out[] = ['label' => $label, 'path' => str_replace(dirname(base_path()), '…', $dir), 'size' => $size, 'files' => $files];
        }

        return $out;
    }

    /** @return array{0:int,1:int} ukuran (byte) dan jumlah file; dibatasi 50 ribu file agar halaman tetap cepat */
    public static function dirSize(string $dir): array
    {
        if (! is_dir($dir)) {
            return [0, 0];
        }
        $size = 0;
        $n = 0;
        try {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile() && ! $f->isLink()) {
                    $size += $f->getSize();
                    if (++$n >= 50000) {
                        break;
                    }
                }
            }
        } catch (Throwable) {
            // folder tanpa izin baca dilewati
        }

        return [$size, $n];
    }

    /**
     * Bersihkan sampah yang aman dihapus: sesi kedaluwarsa, cache tampilan, cache aplikasi, log yang terlalu besar.
     *
     * @return array<int,string> ringkasan yang dikerjakan
     */
    public static function tidy(): array
    {
        $done = [];
        if (config('session.driver') === 'database') {
            $n = DB::table('sessions')->where('last_activity', '<', now()->subMinutes((int) config('session.lifetime'))->getTimestamp())->delete();
            $done[] = "{$n} sesi kedaluwarsa dihapus";
        }
        if (DB::getSchemaBuilder()->hasTable('cache')) {
            $n = DB::table('cache')->where('expiration', '<', time())->delete();
            $done[] = "{$n} cache kedaluwarsa dihapus";
        }
        Artisan::call('view:clear');
        $done[] = 'cache tampilan dibuat ulang saat dibuka';
        $done[] = self::trimLog() ?? 'log masih kecil, tidak dipangkas';

        return $done;
    }

    /** File log yang sedang dipakai: laravel.log (mode single) atau laravel-YYYY-MM-DD.log terbaru (mode daily). */
    public static function currentLog(): ?string
    {
        clearstatcache();
        $files = glob(storage_path('logs/laravel*.log')) ?: [];
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        return $files[0] ?? null;
    }

    /** Pangkas log aktif bila lebih dari 2 MB: simpan 512 KB terakhir. */
    public static function trimLog(int $max = 2097152, int $keep = 524288): ?string
    {
        $log = self::currentLog() ?? storage_path('logs/laravel.log');
        clearstatcache(true, $log);
        if (! is_file($log) || filesize($log) <= $max) {
            return null;
        }
        $before = filesize($log);
        $fh = fopen($log, 'rb');
        fseek($fh, -$keep, SEEK_END);
        $tail = stream_get_contents($fh);
        fclose($fh);
        // mulai dari awal entri log yang utuh
        $pos = strpos($tail, "\n[");
        file_put_contents($log, $pos === false ? $tail : substr($tail, $pos + 1), LOCK_EX);
        clearstatcache(true, $log);

        return 'log dipangkas dari '.self::bytes($before).' menjadi '.self::bytes(filesize($log));
    }

    /** @return array<int,array{time:string,level:string,message:string}> entri error terbaru (paling baru di atas) */
    public static function recentErrors(int $limit = 15): array
    {
        $log = self::currentLog();
        if (! $log) {
            return [];
        }
        $fh = fopen($log, 'rb');
        $len = filesize($log);
        fseek($fh, max(0, $len - 262144));
        $chunk = stream_get_contents($fh);
        fclose($fh);
        preg_match_all('/^\[(\d{4}-\d\d-\d\d[ T][\d:]+)[^\]]*\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY|WARNING): (.{0,300})/m', $chunk, $m, PREG_SET_ORDER);
        $out = array_map(fn ($r) => ['time' => $r[1], 'level' => $r[2], 'message' => trim($r[3])], $m);

        return array_slice(array_reverse($out), 0, $limit);
    }

    public static function bytes(int $b): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $u) {
            if ($b < 1024 || $u === 'GB') {
                return ($u === 'B' ? $b : number_format($b, 1, ',', '.')).' '.$u;
            }
            $b /= 1024;
        }

        return '';
    }
}
