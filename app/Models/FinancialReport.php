<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Laporan keuangan bulanan. Total dan saldo akhir selalu dihitung dari rincian (tidak pernah diketik),
 * dan laporan baru tayang setelah disetujui pengurus kedua yang berbeda dari pengaju.
 */
class FinancialReport extends Model
{
    use Concerns\Auditable;

    public const STATUSES = ['draf' => 'Draf', 'diajukan' => 'Menunggu persetujuan', 'terbit' => 'Terbit'];

    /** Field yang bila diubah membuat laporan terbit kembali menjadi draf. */
    private const MONEY = ['period', 'opening_balance', 'incomes', 'expenses'];

    protected $fillable = ['period', 'opening_balance', 'incomes', 'expenses', 'poster', 'source_note'];

    protected function casts(): array
    {
        return ['period' => 'immutable_date', 'opening_balance' => 'integer', 'incomes' => 'array', 'expenses' => 'array', 'approved_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $r) {
            $r->period = CarbonImmutable::parse($r->period)->startOfMonth();
            $r->incomes = self::clean($r->incomes);
            $r->expenses = self::clean($r->expenses);
            if ($r->exists && $r->status !== 'draf' && $r->isDirty(self::MONEY)) {
                $r->status = 'draf';            // angka berubah: wajib diajukan dan disetujui ulang
                $r->approved_by = null;
                $r->approved_at = null;
            }
        });
    }

    private static function clean(?array $items): array
    {
        return collect($items ?? [])
            ->map(fn ($i) => ['label' => trim((string) ($i['label'] ?? '')), 'note' => filled($i['note'] ?? null) ? trim($i['note']) : null, 'amount' => (int) ($i['amount'] ?? 0)])
            ->filter(fn ($i) => $i['label'] !== '')
            ->values()->all();
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'terbit');
    }

    public function submitter(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function incomeTotal(): int { return (int) collect($this->incomes)->sum('amount'); }
    public function expenseTotal(): int { return (int) collect($this->expenses)->sum('amount'); }
    public function closingBalance(): int { return $this->opening_balance + $this->incomeTotal() - $this->expenseTotal(); }

    public function label(): string { return $this->period->translatedFormat('F Y'); }
    public function slug(): string { return $this->period->format('Y-m'); }

    /** Laporan bulan sebelumnya (untuk mencocokkan saldo awal). */
    public function previous(): ?self
    {
        return static::query()->where('period', $this->period->subMonth()->toDateString())->first();
    }

    /** Selisih saldo awal dengan saldo akhir bulan sebelumnya; null bila tidak ada pembanding. */
    public function openingGap(): ?int
    {
        $prev = $this->previous();

        return $prev ? $this->opening_balance - $prev->closingBalance() : null;
    }

    public static function rupiah(int $n): string
    {
        return ($n < 0 ? '-' : '').'Rp '.number_format(abs($n), 0, ',', '.');
    }
}
