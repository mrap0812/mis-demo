<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class Post extends Model
{
    use Concerns\Auditable;

    use SoftDeletes;

    public const TYPES = ['berita' => 'Berita', 'pengumuman' => 'Pengumuman', 'artikel' => 'Artikel'];

    protected $fillable = [
        'type', 'category', 'title', 'slug', 'excerpt', 'body', 'cover', 'status',
        'published_at', 'author_id', 'reviewer_id', 'review_note', 'source_url',
    ];

    protected function casts(): array
    {
        return ['status' => PostStatus::class, 'published_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saved(function ($m) {
            if ($m->cover && ($m->wasRecentlyCreated || $m->wasChanged('cover'))) {
                \App\Support\Img::make($m->cover);
            }
        });
        static::saving(function (Post $post) {
            if (blank($post->slug)) {
                $base = Str::slug($post->title);
                $slug = $base;
                $i = 2;
                while (static::withTrashed()->where('slug', $slug)->whereKeyNot($post->getKey())->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $post->slug = $slug;
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** Hanya tulisan berstatus terbit dengan tanggal terbit yang sudah lewat yang boleh tampil ke publik. */
    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', PostStatus::Terbit->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /** Isi HTML yang sudah dibersihkan (spesifikasi §22: sanitasi rich text sebelum ditampilkan). */
    public function safeBody(bool $dropLeadImage = false): string
    {
        $body = (string) $this->body;
        // Tulisan impor menyimpan URL gambar absolut dari host saat impor (mis. 127.0.0.1); arahkan ke host yang berjalan.
        $base = rtrim(Storage::disk('public')->url(''), '/').'/';
        $body = preg_replace('#https?://(?:127\.0\.0\.1|localhost)(?::\d+)?/storage/#i', $base, $body);
        // Bila sampul sudah tampil, gambar pembuka yang sama di awal isi (pola WordPress) tidak perlu diulang.
        if ($dropLeadImage) {
            $body = preg_replace('#^\s*(?:<p>\s*</p>\s*)*<figure[^>]*>\s*(?:<a[^>]*>\s*)?<img[^>]*>\s*(?:</a>\s*)?(?:<figcaption.*?</figcaption>\s*)?</figure>#is', '', $body, 1);
        }

        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->allowRelativeMedias();

        return self::liteYoutube((new HtmlSanitizer($config))->sanitize($body));
    }

    /** Perkiraan waktu baca (200 kata per menit), minimal 1 menit. */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->body)) / 200));
    }

    /**
     * Tautan YouTube yang berdiri sendiri diubah menjadi pemutar ringan: tidak memuat apa pun dari YouTube
     * sampai pembaca menekan tombol putar. Dijalankan SETELAH sanitasi; ID video divalidasi ketat.
     */
    public static function liteYoutube(string $html): string
    {
        return preg_replace_callback(
            // Catatan: sanitizer menulis "=" di atribut sebagai "&#61;", jadi keduanya diterima.
            '#(?:<figure[^>]*>\s*(?:<div[^>]*>)?\s*)?<a href="https?://(?:www\.)?(?:youtube\.com/watch\?v(?:=|&\#61;)|youtu\.be/)([\w-]{11})[^"]*"[^>]*>[^<]*</a>(?:\s*</div>)?(?:\s*</figure>)?#',
            fn ($m) => '<div class="yt" data-yt="'.$m[1].'"><button type="button" class="yt-play" aria-label="Putar video di halaman ini">'
                .'<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path fill="currentColor" d="M8 5.5v13l11-6.5z"/></svg><span>Putar video</span></button>'
                .'<a class="yt-out" href="https://www.youtube.com/watch?v='.$m[1].'" target="_blank" rel="noopener">Buka di YouTube</a></div>',
            $html,
        );
    }
}
