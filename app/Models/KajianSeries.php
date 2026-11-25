<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KajianSeries extends Model
{
    use Concerns\Auditable;

    public const SLOTS = ['subuh' => "Ba'da Subuh", 'ashar' => "Ba'da Ashar", 'maghrib' => "Ba'da Maghrib", 'jumat' => 'Siang (Jumat)'];
    public const SLOT_ORDER = ['subuh' => 0, 'jumat' => 1, 'ashar' => 2, 'maghrib' => 3];
    public const CATEGORIES = ['kajian' => 'Kajian rutin', 'berkala' => 'Kajian berkala', 'muslimah' => 'Kajian Muslimah', 'khutbah' => 'Khutbah Jumat'];
    public const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Ahad'];

    /** Warna kartu dibatasi ke palet situs (merah, merah tua, emas) agar tampilan tetap satu bahasa visual. */
    public const TONES = ['#A3121A' => 'Merah', '#6B0E13' => 'Merah tua', '#9A7019' => 'Emas'];

    protected $table = 'kajian_series';

    protected static function booted(): void
    {
        static::saving(fn (self $s) => $s->color = self::tone($s->color));
    }

    /** Petakan warna bebas (data lama, impor) ke salah satu TONES: gelap atau pucat ke merah tua, jingga sampai hijau-biru ke emas, sisanya (biru, ungu, merah muda, merah) ke merah. */
    public static function tone(?string $hex): string
    {
        $hex = strtoupper((string) $hex);
        if (isset(self::TONES[$hex])) {
            return $hex;
        }
        if (! preg_match('/^#([0-9A-F]{2})([0-9A-F]{2})([0-9A-F]{2})$/', $hex, $m)) {
            return array_key_first(self::TONES);
        }
        [$r, $g, $b] = [hexdec($m[1]) / 255, hexdec($m[2]) / 255, hexdec($m[3]) / 255];
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;
        $sat = $d == 0 ? 0 : $d / (1 - abs(2 * $l - 1));
        $h = match (true) {
            $d == 0 => 0,
            $max === $r => 60 * fmod(($g - $b) / $d + 6, 6),
            $max === $g => 60 * (($b - $r) / $d + 2),
            default => 60 * (($r - $g) / $d + 4),
        };

        return match (true) {
            $l < .25 || $sat < .25 => '#6B0E13',
            $h >= 20 && $h < 200 => '#9A7019',
            default => '#A3121A',
        };
    }

    protected $fillable = [
        'code', 'title', 'kitab', 'kitab_ar', 'category', 'slot', 'day_of_week', 'weeks', 'except_weeks',
        'badge', 'color', 'speaker_id', 'is_tentative', 'is_active', 'playlist_url', 'source', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'weeks' => 'array',
            'except_weeks' => 'array',
            'is_tentative' => 'boolean',
            'is_active' => 'boolean',
            'day_of_week' => 'integer',
        ];
    }

    public function speaker(): BelongsTo
    {
        return $this->belongsTo(Speaker::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(KajianSession::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /** Pekan ke-n dalam bulan Masehi (1..5). */
    public static function weekOfMonth(CarbonInterface $date): int
    {
        return intdiv($date->day - 1, 7) + 1;
    }

    /** Apakah seri ini berlangsung pada tanggal tertentu menurut aturan jadwalnya. */
    public function occursOn(CarbonInterface $date): bool
    {
        if (! $this->is_active || $this->is_tentative || $date->dayOfWeek !== $this->day_of_week) {
            return false;
        }
        $n = self::weekOfMonth($date);
        if (! empty($this->weeks) && ! in_array($n, $this->weeks, true)) {
            return false;
        }
        if (! empty($this->except_weeks) && in_array($n, $this->except_weeks, true)) {
            return false;
        }

        return true;
    }

    public function slotLabel(): string
    {
        return self::SLOTS[$this->slot] ?? $this->slot;
    }
}
