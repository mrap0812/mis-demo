<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use IntlCalendar;
use IntlTimeZone;

/**
 * Kampanye donasi. Jenis "target": persentase dan sisa kebutuhan dihitung dari target dan dana terkumpul.
 * Jenis "ramadhan": hitung mundur ke 1 Ramadhan dihitung dari kalender Umm al-Qura, tampil sampai akhir Ramadhan.
 * Rekening hanya ditampilkan bila rekening itu sudah disetujui (DonationAccount live).
 */
class Campaign extends Model
{
    use Concerns\Auditable;

    public const KINDS = ['target' => 'Target dana', 'ramadhan' => 'Hitung mundur Ramadhan'];

    protected $fillable = ['title', 'slug', 'kind', 'summary', 'target_amount', 'collected_amount', 'data_date', 'hijri_year', 'quote', 'quote_ref',
        'donation_account_id', 'poster_bank', 'poster_account_number', 'poster_account_holder', 'poster', 'poster_alt', 'source', 'is_active', 'sort'];

    protected function casts(): array
    {
        return ['data_date' => 'immutable_date', 'is_active' => 'boolean', 'target_amount' => 'integer', 'collected_amount' => 'integer', 'hijri_year' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(fn (self $c) => $c->slug = $c->slug ?: \Illuminate\Support\Str::slug($c->title));
        static::saved(fn (self $c) => \App\Support\Img::make($c->poster));
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(DonationAccount::class, 'donation_account_id');
    }

    /** Rekening yang boleh ditampilkan: hanya yang sudah disetujui dan aktif. */
    public function liveAccount(): ?DonationAccount
    {
        return $this->donation_account_id ? DonationAccount::query()->live()->find($this->donation_account_id) : null;
    }

    /** Kampanye aktif yang masih relevan pada tanggal tertentu (Ramadhan: hilang sendiri setelah 1 Syawal). */
    public function scopeVisible(Builder $q, ?CarbonImmutable $today = null): Builder
    {
        return $q->where('is_active', true)->orderBy('sort');
    }

    public static function forPublic(CarbonImmutable $today)
    {
        return static::query()->visible()->get()->filter(fn (self $c) => $c->kind !== 'ramadhan' || ($c->ramadhanEnd() && $today->lt($c->ramadhanEnd())))->values();
    }

    // -------------------------------------------------- target dana
    public function percent(): float
    {
        return $this->target_amount ? min(100, round($this->collected_amount / $this->target_amount * 100, 2)) : 0;
    }

    public function remaining(): int
    {
        return max(0, (int) $this->target_amount - (int) $this->collected_amount);
    }

    // -------------------------------------------------- Ramadhan
    private static function hijriToDate(int $year, int $month): ?CarbonImmutable
    {
        $cal = IntlCalendar::createInstance(IntlTimeZone::createTimeZone('Asia/Makassar'), 'id_ID@calendar=islamic-umalqura');
        if (! $cal) {
            return null;
        }
        $cal->clear();
        $cal->set($year, $month - 1, 1, 12, 0, 0);

        return CarbonImmutable::createFromTimestamp((int) ($cal->getTime() / 1000), 'Asia/Makassar')->startOfDay();
    }

    public function ramadhanStart(): ?CarbonImmutable
    {
        return $this->hijri_year ? self::hijriToDate($this->hijri_year, 9) : null;
    }

    public function ramadhanEnd(): ?CarbonImmutable
    {
        return $this->hijri_year ? self::hijriToDate($this->hijri_year, 10) : null; // 1 Syawal
    }

    /** Sisa hari menuju 1 Ramadhan; 0 bila Ramadhan sedang berlangsung. Perkiraan, bisa bergeser 1 hari menurut rukyat. */
    public function daysToRamadhan(CarbonImmutable $today): ?int
    {
        $start = $this->ramadhanStart();

        return $start ? max(0, (int) $today->startOfDay()->diffInDays($start, false)) : null;
    }

    public static function rupiah(int $n): string
    {
        return 'Rp '.number_format($n, 0, ',', '.');
    }
}
