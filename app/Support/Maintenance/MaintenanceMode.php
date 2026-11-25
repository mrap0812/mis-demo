<?php

namespace App\Support\Maintenance;

use App\Models\Setting;
use Carbon\CarbonImmutable;

/**
 * Mode perbaikan buatan sendiri (pengganti plugin Maintenance/WP Maintenance Mode).
 * Berbeda dengan `php artisan down`, panel admin tetap bisa dibuka, jadi admin tidak pernah terkunci di luar.
 */
class MaintenanceMode
{
    public const KEYS = ['maintenance_active', 'maintenance_title', 'maintenance_text', 'maintenance_until'];

    /** Dibaca langsung dari database (bukan cache) agar menyala/mati seketika. */
    public static function active(?array $s = null): bool
    {
        $s ??= array_replace(
            array_intersect_key(Setting::DEFAULTS, array_flip(self::KEYS)),
            Setting::query()->whereIn('key', self::KEYS)->pluck('value', 'key')->all(),
        );
        if (! $s['maintenance_active']) {
            return false;
        }

        return blank($s['maintenance_until']) || CarbonImmutable::now('Asia/Makassar')->lt(CarbonImmutable::parse($s['maintenance_until'], 'Asia/Makassar'));
    }
}
