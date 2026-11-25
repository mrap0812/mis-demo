<?php

namespace App\Support\Maintenance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Cadangan database dalam bentuk SQL murni, ditulis dengan PHP sendiri.
 * Alasannya: hosting bersama sering mematikan exec(), sehingga mysqldump tidak bisa dipanggil.
 * Hasilnya bisa langsung diimpor lewat phpMyAdmin (MySQL/MariaDB).
 */
class DatabaseBackup
{
    /** Tabel sementara: strukturnya ikut, isinya tidak (sesi login, cache). */
    public const SKIP_DATA = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'];

    private const CHUNK = 300;

    public static function dir(): string
    {
        return storage_path('app/private/backups');
    }

    /**
     * Tulis cadangan lengkap ke file. Mengembalikan path file.
     *
     * @param  array<int,string>  $skipData  tabel yang hanya disalin strukturnya
     */
    public static function create(?string $path = null, bool $gzip = true, array $skipData = self::SKIP_DATA): string
    {
        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) {
            throw new RuntimeException("Driver database {$driver} belum didukung.");
        }

        if ($path === null) {
            @mkdir(self::dir(), 0750, true);
            $path = self::dir().'/mis-'.now('Asia/Makassar')->format('Ymd-His').($gzip ? '.sql.gz' : '.sql');
        }
        $tmp = $path.'.part';
        $fh = $gzip ? gzopen($tmp, 'wb6') : fopen($tmp, 'wb');
        if (! $fh) {
            throw new RuntimeException('Folder cadangan tidak bisa ditulisi.');
        }
        $write = $gzip ? fn (string $s) => gzwrite($fh, $s) : fn (string $s) => fwrite($fh, $s);

        $write("-- Cadangan database situs Masjid Imam Syafi'i Banjarmasin\n");
        $write('-- Dibuat: '.now('Asia/Makassar')->format('Y-m-d H:i:s')." WITA\n");
        $write("-- Impor: phpMyAdmin > pilih database > Impor > pilih file ini.\n\n");
        $write("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

        $pdo = DB::connection()->getPdo();
        foreach (self::tables() as $table) {
            $write("DROP TABLE IF EXISTS `{$table}`;\n".self::createStatement($table, $driver).";\n\n");
            if (in_array($table, $skipData, true)) {
                continue;
            }
            $cols = Schema::getColumnListing($table);
            $colList = '`'.implode('`,`', $cols).'`';
            $orderBy = in_array('id', $cols, true) ? 'id' : $cols[0];
            DB::table($table)->orderBy($orderBy)->chunk(self::CHUNK, function ($rows) use ($write, $table, $colList, $pdo) {
                $values = [];
                foreach ($rows as $row) {
                    $values[] = '('.implode(',', array_map(
                        fn ($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string) $v : $pdo->quote((string) $v)),
                        (array) $row,
                    )).')';
                }
                $write("INSERT INTO `{$table}` ({$colList}) VALUES\n".implode(",\n", $values).";\n");
            });
            $write("\n");
        }
        $write("SET FOREIGN_KEY_CHECKS=1;\n");
        $gzip ? gzclose($fh) : fclose($fh);
        rename($tmp, $path);

        return $path;
    }

    /** @return array<int,string> */
    public static function tables(): array
    {
        return collect(Schema::getTables())->pluck('name')
            ->reject(fn ($t) => str_starts_with($t, 'sqlite_'))->sort()->values()->all();
    }

    private static function createStatement(string $table, string $driver): string
    {
        if ($driver === 'sqlite') {
            // Cadangan lokal (pengembangan). Untuk server, cadangan dibuat dari MariaDB/MySQL.
            return (string) DB::scalar('select sql from sqlite_master where type = ? and name = ?', ['table', $table]);
        }
        $row = (array) DB::selectOne("SHOW CREATE TABLE `{$table}`");

        // AUTO_INCREMENT=n tidak perlu: nilainya dipulihkan dari data.
        return preg_replace('/\sAUTO_INCREMENT=\d+/', '', $row['Create Table'] ?? array_values($row)[1]);
    }

    /** @return array<int,array{name:string,size:int,time:int}> terbaru di atas */
    public static function list(): array
    {
        $files = glob(self::dir().'/mis-*.sql*') ?: [];
        $files = array_filter($files, fn ($f) => ! str_ends_with($f, '.part'));
        $out = array_map(fn ($f) => ['name' => basename($f), 'size' => filesize($f), 'time' => filemtime($f)], $files);
        usort($out, fn ($a, $b) => $b['time'] <=> $a['time']);

        return $out;
    }

    /** Nama file aman (tanpa folder) yang benar-benar ada di folder cadangan. */
    public static function path(string $name): ?string
    {
        if (! preg_match('/^mis-\d{8}-\d{6}\.sql(\.gz)?$/', $name)) {
            return null;
        }
        $p = self::dir().'/'.$name;

        return is_file($p) ? $p : null;
    }

    /** Simpan $keep cadangan terbaru, hapus sisanya. Mengembalikan jumlah yang dihapus. */
    public static function prune(int $keep): int
    {
        $n = 0;
        foreach (array_slice(self::list(), max(1, $keep)) as $f) {
            $n += (int) @unlink(self::dir().'/'.$f['name']);
        }

        return $n;
    }
}
