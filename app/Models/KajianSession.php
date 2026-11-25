<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Data per pertemuan. Hanya dibuat bila ada informasi khusus (judul dari poster, batal/tunda,
 * rekaman). Pertemuan tanpa baris di tabel ini tetap tampil dari aturan jadwal seri.
 */
class KajianSession extends Model
{
    use Concerns\Auditable;

    public const STATUSES = ['terjadwal' => 'Terjadwal', 'ditunda' => 'Ditunda', 'dibatalkan' => 'Dibatalkan'];

    protected $fillable = [
        'kajian_series_id', 'date', 'title', 'speaker_id', 'status', 'status_reason',
        'poster', 'recording_url', 'notes', 'source',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(KajianSeries::class, 'kajian_series_id');
    }

    public function speaker(): BelongsTo
    {
        return $this->belongsTo(Speaker::class);
    }
}
