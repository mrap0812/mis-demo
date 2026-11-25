<?php

namespace App\Models;

use App\Support\SafeHtml;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Service extends Model
{
    use Concerns\Auditable;

    public const ICONS = [
        'book' => 'Buku / Al-Qur\'an', 'ambulance' => 'Ambulans', 'shroud' => 'Jenazah', 'heart' => 'Kepedulian',
        'drop' => 'Donor darah', 'users' => 'Kelompok / komunitas', 'chat' => 'Konsultasi', 'cal' => 'Jadwal / acara',
    ];

    public const STATUSES = ['tersedia' => 'Tersedia', 'menunggu' => 'Detail menunggu penetapan pengurus'];

    protected $fillable = ['name', 'slug', 'icon', 'summary', 'details', 'schedule', 'contact_label', 'contact_number',
        'registration_url', 'status', 'is_active', 'sort'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Service $s) {
            if (blank($s->slug)) {
                $s->slug = Str::slug($s->name);
            }
        });
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort')->orderBy('name');
    }

    public function safeDetails(): string
    {
        return SafeHtml::clean($this->details);
    }
}
