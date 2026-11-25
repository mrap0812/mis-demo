<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/** Rekening/QRIS yang tampil di halaman Infak. Tidak pernah diubah langsung; lihat DonationAccountChange. */
class DonationAccount extends Model
{
    use Concerns\Auditable;

    public const KINDS = ['rekening' => 'Rekening bank', 'qris' => 'QRIS'];

    /** Kolom yang boleh diajukan perubahannya. */
    public const FIELDS = ['kind', 'purpose', 'bank_name', 'account_number', 'account_holder', 'qris_image', 'qris_merchant', 'notes', 'sort'];

    public const LABELS = [
        'kind' => 'Jenis', 'purpose' => 'Peruntukan', 'bank_name' => 'Bank', 'account_number' => 'Nomor rekening',
        'account_holder' => 'Atas nama', 'qris_image' => 'Gambar QRIS', 'qris_merchant' => 'Nama merchant / NMID',
        'notes' => 'Catatan', 'sort' => 'Urutan',
    ];

    protected $fillable = [...self::FIELDS, 'is_active', 'approved_by', 'approved_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'approved_at' => 'datetime', 'sort' => 'integer'];
    }

    public function scopeLive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort')->orderBy('purpose');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(DonationAccountChange::class);
    }

    public function pendingChange(): ?DonationAccountChange
    {
        return $this->changes()->where('status', 'menunggu')->first();
    }

    public function qrisUrl(): ?string
    {
        return $this->qris_image ? Storage::disk('public')->url($this->qris_image) : null;
    }
}
