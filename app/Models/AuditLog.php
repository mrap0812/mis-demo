<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'auditable_type', 'auditable_id', 'changes', 'ip'];

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Catatan audit tidak boleh diubah atau dihapus lewat aplikasi (spesifikasi §22).
        static::updating(fn () => throw new LogicException('Log audit tidak boleh diubah.'));
        static::deleting(fn () => throw new LogicException('Log audit tidak boleh dihapus.'));
    }

    /** Catat tindakan pemeliharaan sistem (bukan perubahan data tertentu). */
    public static function system(string $action, string $what, array $detail = []): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => mb_substr($action, 0, 12),
            'auditable_type' => 'Sistem',
            'auditable_id' => 0,
            'changes' => ['tindakan' => [null, $what]] + collect($detail)->map(fn ($v) => [null, $v])->all(),
            'ip' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
