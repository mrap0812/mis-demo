<?php

namespace App\Models;

use App\Support\SafeHtml;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Halaman statis (Profil, Sejarah, Fasilitas, dan sebagainya). */
class ContentPage extends Model
{
    use Concerns\Auditable;

    /** Halaman yang dipakai rute khusus; slug-nya tidak boleh diganti atau dihapus. */
    public const RESERVED = ['profil'];

    protected $table = 'pages';

    protected $fillable = ['slug', 'title', 'lede', 'body', 'cover', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(function ($m) {
            if ($m->cover && ($m->wasRecentlyCreated || $m->wasChanged('cover'))) {
                \App\Support\Img::make($m->cover);
            }
        });
        static::saving(function (ContentPage $p) {
            if (blank($p->slug)) {
                $p->slug = Str::slug($p->title);
            }
            if ($p->exists && $p->isDirty('slug') && in_array($p->getOriginal('slug'), self::RESERVED, true)) {
                $p->slug = $p->getOriginal('slug');
            }
        });
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function isReserved(): bool
    {
        return in_array($this->slug, self::RESERVED, true);
    }

    public function safeBody(): string
    {
        return SafeHtml::clean($this->body);
    }

    public function url(): string
    {
        return $this->slug === 'profil' ? route('profil') : route('halaman', $this->slug);
    }
}
