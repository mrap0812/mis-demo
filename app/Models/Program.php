<?php

namespace App\Models;

use App\Support\SafeHtml;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Program extends Model
{
    use Concerns\Auditable;

    public const CATEGORIES = [
        'pendidikan' => "Pendidikan & Al-Qur'an",
        'sosial' => 'Sosial & lingkungan',
        'dakwah' => 'Dakwah',
        'ekonomi' => 'Ekonomi & UMKM',
        'lainnya' => 'Lainnya',
    ];

    public const CATEGORY_ICONS = ['pendidikan' => 'book', 'sosial' => 'leaf', 'dakwah' => 'users', 'ekonomi' => 'gift', 'lainnya' => 'heart'];

    protected $fillable = ['title', 'slug', 'category', 'tagline', 'summary', 'body', 'schedule', 'days', 'location', 'audience', 'organizer', 'organizer_url', 'is_free',
        'items_title', 'items', 'notes_title', 'notes', 'steps', 'closing', 'contact_label', 'contact_number', 'registration_url',
        'poster', 'poster_alt', 'source', 'is_published', 'sort'];

    protected function casts(): array
    {
        return ['days' => 'array', 'items' => 'array', 'notes' => 'array', 'steps' => 'array', 'is_free' => 'boolean', 'is_published' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(function ($m) {
            if ($m->poster && ($m->wasRecentlyCreated || $m->wasChanged('poster'))) {
                \App\Support\Img::make($m->poster);
            }
        });
        static::saving(function (Program $p) {
            if (blank($p->slug)) {
                $p->slug = Str::slug($p->title);
            }
        });
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true)->orderBy('sort')->orderBy('title');
    }

    public const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Ahad'];

    /** 0 = hari ini, 1 = besok, dst. sampai 6; null bila hari pelaksanaan belum diisi. */
    public function daysUntil(\Carbon\CarbonInterface $today): ?int
    {
        $days = array_map('intval', $this->days ?? []);
        if (! $days) {
            return null;
        }

        return min(array_map(fn ($d) => ($d - $today->dayOfWeek + 7) % 7, $days));
    }

    /** "Hari ini", "Besok", atau null. */
    public function whenLabel(\Carbon\CarbonInterface $today): ?string
    {
        return match ($this->daysUntil($today)) { 0 => 'Hari ini', 1 => 'Besok', default => null };
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Program';
    }

    public function icon(): string
    {
        return self::CATEGORY_ICONS[$this->category] ?? 'heart';
    }

    public function safeBody(): string
    {
        return SafeHtml::clean($this->body);
    }

    /** Daftar (isi repeater) tanpa baris kosong. */
    public function list(string $field): array
    {
        return collect($this->{$field} ?? [])
            ->map(fn ($v) => is_array($v) ? array_filter($v, 'filled') : trim((string) $v))
            ->filter(fn ($v) => filled($v))->values()->all();
    }
}
