<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Versi kecil gambar unggahan (lebar 240, 480, 720, dan 960 px, WebP bila server mendukung) untuk atribut srcset.
 * Dibuat saat gambar disimpan atau lewat `php artisan mis:thumbs` / panel Pemeliharaan; tidak pernah saat halaman dibuka,
 * sehingga pengunjung tidak menunggu. Bila versi kecil belum ada, halaman memakai gambar asli seperti biasa.
 */
final class Img
{
    public const WIDTHS = [240, 480, 720, 960];

    public const DIR = '_thumbs';

    public static function webp(): bool
    {
        return function_exists('imagewebp') && (gd_info()['WebP Support'] ?? false);
    }

    public static function variant(string $path, int $w): string
    {
        return self::DIR.'/'.preg_replace('/\.[a-z0-9]+$/i', '', ltrim($path, '/')).'-'.$w.(self::webp() ? '.webp' : '.jpg');
    }

    public static function url(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }

    /** "url-480 480w, url-960 960w, url-asli 1600w" atau null bila belum ada versi kecil. */
    public static function srcset(?string $path): ?string
    {
        if (! $path || str_contains($path, '..')) {
            return null;
        }
        $disk = Storage::disk('public');
        $parts = [];
        foreach (self::WIDTHS as $w) {
            if (is_file($disk->path(self::variant($path, $w)))) {
                $parts[] = $disk->url(self::variant($path, $w)).' '.$w.'w';
            }
        }
        if (! $parts) {
            return null;
        }
        $size = @getimagesize($disk->path($path));
        if ($size) {
            $parts[] = $disk->url($path).' '.$size[0].'w';
        }

        return implode(', ', $parts);
    }

    /** Atribut srcset + sizes siap tempel di dalam tag <img>, atau string kosong. */
    public static function attrs(?string $path, string $sizes): string
    {
        $set = self::srcset($path);

        return $set ? ' srcset="'.e($set).'" sizes="'.e($sizes).'"' : '';
    }

    /** Buat versi kecil satu gambar. Mengembalikan jumlah file yang dibuat. */
    public static function make(?string $path): int
    {
        if (! $path || str_contains($path, '..') || str_starts_with($path, self::DIR.'/')) {
            return 0;
        }
        // GD memuat gambar utuh ke memori; foto ponsel bisa melebihi batas umum 128 MB di hosting.
        $limit = trim((string) ini_get('memory_limit'));
        $bytes = $limit === '-1' ? PHP_INT_MAX : (int) $limit * match (strtoupper(substr($limit, -1))) { 'G' => 1 << 30, 'M' => 1 << 20, 'K' => 1 << 10, default => 1 };
        if ($bytes < (512 << 20)) {
            @ini_set('memory_limit', '512M');
        }
        $disk = Storage::disk('public');
        $src = $disk->path($path);
        $info = @getimagesize($src);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return 0;
        }
        $made = 0;
        try {
            $img = null;
            foreach (self::WIDTHS as $w) {
                $dest = $disk->path(self::variant($path, $w));
                if ($w >= $info[0] || (is_file($dest) && filemtime($dest) >= filemtime($src))) {
                    continue;
                }
                $img ??= match ($info[2]) { IMAGETYPE_JPEG => imagecreatefromjpeg($src), IMAGETYPE_PNG => imagecreatefrompng($src), default => imagecreatefromwebp($src) };
                $h = (int) round($info[1] * $w / $info[0]);
                $out = imagecreatetruecolor($w, $h);
                imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
                imagecopyresampled($out, $img, 0, 0, 0, 0, $w, $h, $info[0], $info[1]);
                @mkdir(dirname($dest), 0755, true);
                self::webp() ? imagewebp($out, $dest, 78) : imagejpeg($out, $dest, 78);
                $made++;
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $made;
    }

    /** Semua gambar di disk publik (kecuali folder versi kecil itu sendiri). */
    public static function makeAll(): array
    {
        $files = 0;
        $made = 0;
        foreach (Storage::disk('public')->allFiles() as $f) {
            if (str_starts_with($f, self::DIR.'/') || ! preg_match('/\.(jpe?g|png|webp)$/i', $f)) {
                continue;
            }
            $files++;
            $made += self::make($f);
        }

        return ['files' => $files, 'made' => $made];
    }
}
