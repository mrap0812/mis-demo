<?php

namespace App\Console\Commands;

use App\Enums\PostStatus;
use App\Models\Post;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Memindahkan tulisan dari WordPress mis.id (ekspor JSON) beserta gambar/PDF-nya.
 * Aman dijalankan berulang: tulisan dicocokkan lewat source_url, file yang sudah ada tidak disalin ulang.
 * Judul, slug, kategori, dan sampul yang sudah dirapikan di panel tidak ditimpa; yang diperbarui hanya isi.
 */
class ImportWordPress extends Command
{
    protected $signature = 'mis:import-wordpress {json : Ekspor tulisan (wp-posts.json)} {uploads : Folder wp-content/uploads dari cPanel} {--max-width=1600 : Lebar maksimal gambar}';

    protected $description = 'Impor isi lengkap berita mis.id lama (WordPress) beserta gambar dan PDF';

    private const ORIGIN = '#https?://(?:www\.)?mis\.id/wp-content/uploads/#i';

    public function handle(): int
    {
        // Mengecilkan foto besar (GD) butuh memori lebih dari batas umum 128 MB di hosting.
        @ini_set('memory_limit', '512M');
        $items = json_decode((string) @file_get_contents($this->argument('json')), true);
        $uploads = rtrim($this->argument('uploads'), '/\\');
        if (! is_array($items) || ! is_dir($uploads)) {
            $this->error('File JSON atau folder uploads tidak ditemukan.');

            return self::FAILURE;
        }

        $made = $updated = $files = $missing = 0;
        foreach ($items as $it) {
            foreach (array_merge($it['files'] ?? [], array_filter([$it['cover'] ?? null])) as $rel) {
                $r = $this->copyFile($uploads, $rel);
                $r === null ? $missing++ : ($r && $files++);
            }

            $body = $this->rewrite($it['body']);
            $source = $it['slug'] ? 'https://www.mis.id/'.$it['slug'].'/' : 'https://www.mis.id/?p='.$it['wp_id'];
            $post = Post::withTrashed()->where('source_url', $source)->first();

            if ($post) {
                $post->body = $body;
                $post->cover ??= $it['cover'] ? 'wp/'.$it['cover'] : null;
                $post->save();
                $updated++;
                continue;
            }

            $title = $it['title'];
            if (mb_strtoupper($title) === $title) {          // judul KAPITAL SEMUA dari WordPress
                $title = Str::ucfirst(mb_strtolower($title));
            }
            $date = CarbonImmutable::parse($it['date'], 'Asia/Makassar');
            Post::create([
                'type' => in_array('Berita Kegiatan', $it['categories'] ?? [], true) ? 'berita' : 'artikel',
                'category' => in_array('Berita Kegiatan', $it['categories'] ?? [], true) ? 'Kegiatan' : 'Artikel',
                'title' => $title,
                'excerpt' => $it['excerpt'] ?: Str::limit(trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($body)))), 200),
                'body' => $body,
                'cover' => $it['cover'] ? 'wp/'.$it['cover'] : null,
                'status' => $it['status'] === 'publish' ? PostStatus::Terbit : PostStatus::Draf,
                'published_at' => $it['status'] === 'publish' ? $date : null,
                'source_url' => $source,
            ]);
            $made++;
        }

        $this->info("Selesai: {$made} tulisan baru, {$updated} diperbarui, {$files} file disalin".($missing ? ", {$missing} file tidak ditemukan" : '').'.');

        return self::SUCCESS;
    }

    /** Alamat file lama di mis.id diarahkan ke salinan lokal di /storage/wp/. */
    private function rewrite(string $html): string
    {
        $base = rtrim(Storage::disk('public')->url('wp'), '/').'/';
        $html = preg_replace(self::ORIGIN, $base, $html);
        // Alamat YouTube yang berdiri sendiri (dulu disematkan otomatis oleh WordPress) menjadi tautan yang bisa diklik.
        $html = preg_replace('#(<p>|^|\n)\s*(https?://(?:www\.)?(?:youtube\.com/watch\?v=|youtu\.be/)[\w\-?=&;]+)\s*(</p>|$|\n)#m',
            '$1<a href="$2" target="_blank" rel="noopener">Tonton videonya di YouTube</a>$3', $html);

        return preg_replace('#(["\'])/wp-content/uploads/#', '$1'.$base, $html);
    }

    /** @return bool|null true = disalin, false = sudah ada, null = sumber tidak ada */
    private function copyFile(string $uploads, string $rel): ?bool
    {
        $rel = ltrim(str_replace('\\', '/', $rel), '/');
        if (str_contains($rel, '..')) {
            return null;
        }
        $src = $uploads.'/'.$rel;
        $dest = 'wp/'.$rel;
        if (! is_file($src)) {
            $this->warn("Tidak ada: {$rel}");

            return null;
        }
        $disk = Storage::disk('public');
        if ($disk->exists($dest)) {
            return false;
        }
        $disk->makeDirectory(dirname($dest));
        $target = $disk->path($dest);

        // Perkecil foto besar agar halaman tetap ringan; file lain disalin apa adanya.
        $info = @getimagesize($src);
        $max = (int) $this->option('max-width');
        if ($info && $info[0] > $max && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true) && function_exists('imagecreatetruecolor')) {
            $img = match ($info[2]) { IMAGETYPE_JPEG => imagecreatefromjpeg($src), IMAGETYPE_PNG => imagecreatefrompng($src), default => imagecreatefromwebp($src) };
            $h = (int) round($info[1] * $max / $info[0]);
            $out = imagecreatetruecolor($max, $h);
            if ($info[2] !== IMAGETYPE_JPEG) {
                imagealphablending($out, false);
                imagesavealpha($out, true);
            }
            imagecopyresampled($out, $img, 0, 0, 0, 0, $max, $h, $info[0], $info[1]);
            match ($info[2]) { IMAGETYPE_JPEG => imagejpeg($out, $target, 82), IMAGETYPE_PNG => imagepng($out, $target, 8), default => imagewebp($out, $target, 82) };

            return true;
        }

        return copy($src, $target);
    }
}
