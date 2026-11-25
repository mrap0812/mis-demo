<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Speaker extends Model
{
    use Concerns\Auditable;

    protected $fillable = ['name', 'short_name', 'bio', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function series(): HasMany
    {
        return $this->hasMany(KajianSeries::class);
    }

    public function displayShort(): string
    {
        return $this->short_name ?: preg_replace(['/^Ustadz /', '/, (Lc\.|B\.A\.|M\.A\.).*$/'], ['Ust. ', ''], $this->name);
    }
}
