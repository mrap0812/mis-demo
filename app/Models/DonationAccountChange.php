<?php

namespace App\Models;

use App\Support\SafeHtml;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

/**
 * Pengajuan perubahan rekening/QRIS (persetujuan ganda, "maker-checker").
 * - Satu orang mengajukan, orang LAIN (admin atau bendahara) menyetujui.
 * - Penyetuju wajib mengetik ulang nomor rekening dari sumber aslinya (atau mencentang bahwa QRIS sudah dipindai).
 * - Data publik baru berubah saat pengajuan disetujui; satu rekening hanya boleh punya satu pengajuan yang menunggu.
 */
class DonationAccountChange extends Model
{
    use Concerns\Auditable;

    public const ACTIONS = ['create' => 'Tambah baru', 'update' => 'Ubah', 'retire' => 'Nonaktifkan'];

    public const STATUSES = ['menunggu' => 'Menunggu persetujuan', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'dibatalkan' => 'Dibatalkan'];

    protected $fillable = ['donation_account_id', 'action', 'payload', 'source', 'proposed_by', 'status', 'reviewed_by', 'reviewed_at', 'review_note'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(DonationAccount::class, 'donation_account_id');
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ---------------------------------------------------------------- alur

    public static function propose(?User $by, string $action, ?DonationAccount $account, array $payload = [], ?string $source = null): self
    {
        if ($by && ! $by->isTreasurer()) {
            throw new DomainException('Hanya admin atau bendahara yang boleh mengajukan perubahan rekening.');
        }
        if (! array_key_exists($action, self::ACTIONS) || ($action === 'create') === (bool) $account) {
            throw new DomainException('Jenis pengajuan tidak sesuai.');
        }
        if ($account && $account->pendingChange()) {
            throw new DomainException('Rekening ini sudah punya pengajuan yang menunggu. Selesaikan atau batalkan dulu.');
        }
        $payload = array_intersect_key($payload, array_flip(DonationAccount::FIELDS));
        if ($action === 'update') {
            // Simpan hanya kolom yang benar-benar berubah, supaya penyetuju melihat perbedaannya dengan jelas.
            $payload = array_filter($payload, fn ($v, $k) => (string) $v !== (string) $account->{$k}, ARRAY_FILTER_USE_BOTH);
            if (! $payload) {
                throw new DomainException('Tidak ada perubahan yang diajukan.');
            }
        }

        return self::create([
            'donation_account_id' => $account?->id, 'action' => $action, 'payload' => $action === 'retire' ? null : $payload,
            'source' => $source, 'proposed_by' => $by?->id, 'status' => 'menunggu',
        ]);
    }

    public function canBeApprovedBy(User $user): bool
    {
        return $this->status === 'menunggu' && $user->isTreasurer() && (int) $this->proposed_by !== (int) $user->id;
    }

    /** Nilai akhir setelah pengajuan diterapkan (untuk pengecekan konfirmasi). */
    public function resulting(): array
    {
        return array_merge($this->account?->only(DonationAccount::FIELDS) ?? [], $this->payload ?? []);
    }

    public function needsQrisCheck(): bool
    {
        return $this->action !== 'retire' && ($this->resulting()['kind'] ?? null) === 'qris';
    }

    public function approve(User $by, ?string $confirmation, bool $qrisChecked = false): DonationAccount
    {
        if (! $this->canBeApprovedBy($by)) {
            throw new DomainException('Pengajuan ini tidak bisa Anda setujui. Penyetuju harus admin/bendahara dan bukan pengaju.');
        }
        $final = $this->resulting();
        if ($this->action === 'retire') {
            if (mb_strtoupper(trim((string) $confirmation)) !== 'NONAKTIFKAN') {
                throw new DomainException('Ketik NONAKTIFKAN untuk mengonfirmasi.');
            }
        } elseif ($final['kind'] === 'rekening') {
            if (SafeHtml::digits($confirmation) === '' || SafeHtml::digits($confirmation) !== SafeHtml::digits($final['account_number'] ?? '')) {
                throw new DomainException('Nomor rekening yang Anda ketik tidak sama dengan yang diajukan. Periksa kembali ke sumber aslinya.');
            }
        } elseif (! $qrisChecked) {
            throw new DomainException('Pindai QRIS dan pastikan nama merchant sesuai sebelum menyetujui.');
        }

        return DB::transaction(function () use ($by) {
            $stamp = ['approved_by' => $by->id, 'approved_at' => now()];
            $account = match ($this->action) {
                'create' => DonationAccount::create($this->payload + $stamp + ['is_active' => true]),
                'update' => tap($this->account)->update($this->payload + $stamp),
                'retire' => tap($this->account)->update(['is_active' => false] + $stamp),
            };
            $this->update(['status' => 'disetujui', 'reviewed_by' => $by->id, 'reviewed_at' => now(), 'donation_account_id' => $account->id]);

            return $account;
        });
    }

    public function reject(User $by, string $note): void
    {
        if (! $this->canBeApprovedBy($by)) {
            throw new DomainException('Pengajuan ini tidak bisa Anda tolak.');
        }
        $this->update(['status' => 'ditolak', 'reviewed_by' => $by->id, 'reviewed_at' => now(), 'review_note' => $note]);
    }

    public function cancel(User $by): void
    {
        if ($this->status !== 'menunggu' || ((int) $this->proposed_by !== (int) $by->id && ! $by->isAdmin())) {
            throw new DomainException('Hanya pengaju (atau admin) yang bisa membatalkan pengajuan yang masih menunggu.');
        }
        $this->update(['status' => 'dibatalkan', 'reviewed_by' => $by->id, 'reviewed_at' => now()]);
    }

    // ---------------------------------------------------------------- tampilan

    public function purposeLabel(): string
    {
        return $this->payload['purpose'] ?? $this->account?->purpose ?? '-';
    }

    /** Tabel perbandingan lama dan baru untuk jendela persetujuan. Semua nilai di-escape. */
    public function describe(): HtmlString
    {
        $old = $this->account?->only(DonationAccount::FIELDS) ?? [];
        $rows = $this->action === 'retire'
            ? collect(['purpose', 'bank_name', 'account_number', 'account_holder'])->mapWithKeys(fn ($k) => [$k => [$old[$k] ?? null, null]])
            : collect($this->payload)->mapWithKeys(fn ($v, $k) => [$k => [$old[$k] ?? null, $v]]);
        $fmt = function ($k, $v) {
            if ($v === null || $v === '') {
                return '<span style="opacity:.5">kosong</span>';
            }
            if ($k === 'qris_image') {
                return '<img src="'.e(Storage::disk('public')->url($v)).'" alt="QRIS" style="max-width:180px;border-radius:8px">';
            }
            if ($k === 'kind') {
                return e(DonationAccount::KINDS[$v] ?? $v);
            }

            return e((string) $v);
        };
        $html = '<table style="width:100%;font-size:.875rem;border-collapse:collapse;text-align:left"><tr><th style="padding:4px">Kolom</th>'
            .($this->action === 'create' ? '' : '<th style="padding:4px">Sekarang</th>').'<th style="padding:4px">'.($this->action === 'retire' ? '' : 'Diajukan').'</th></tr>';
        foreach ($rows as $k => [$a, $b]) {
            $html .= '<tr style="border-top:1px solid rgba(127,127,127,.25)"><td style="padding:4px;font-weight:600">'.e(DonationAccount::LABELS[$k] ?? $k).'</td>'
                .($this->action === 'create' ? '' : '<td style="padding:4px">'.$fmt($k, $a).'</td>')
                .'<td style="padding:4px;'.($this->action === 'update' ? 'background:rgba(214,166,64,.15)' : '').'">'.($this->action === 'retire' ? '' : $fmt($k, $b)).'</td></tr>';
        }
        $html .= '</table>';
        $who = $this->proposer?->name ?? 'Data awal sistem';
        $html .= '<p style="margin-top:.5rem;font-size:.8rem;opacity:.75">Diajukan oleh '.e($who).', '.e($this->created_at?->timezone('Asia/Makassar')->translatedFormat('j M Y H.i')).' WITA. Dasar: '.e($this->source ?: '-').'</p>';

        return new HtmlString($html);
    }
}
