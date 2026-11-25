<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Support\Maintenance\MaintenanceMode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Menampilkan halaman "sedang diperbarui" ke pengunjung, kecuali panel admin dan pengguna yang sudah masuk. */
class SiteMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $s = Setting::query()->whereIn('key', MaintenanceMode::KEYS)->pluck('value', 'key')->all()
                + array_intersect_key(Setting::DEFAULTS, array_flip(MaintenanceMode::KEYS));
            $on = MaintenanceMode::active($s);
        } catch (Throwable) {
            return $next($request); // database belum siap (instalasi awal): jangan menghalangi
        }
        if (! $on || $request->user() || $this->exempt($request)) {
            return $next($request);
        }

        return response()->view('errors.maintenance', ['s' => $s], 503)
            ->header('Retry-After', '3600')
            ->header('X-Robots-Tag', 'noindex');
    }

    private function exempt(Request $request): bool
    {
        $admin = config('mis.admin_path');

        return $request->is($admin, $admin.'/*', 'livewire*', 'filament/*', 'up', 'pasang', 'storage/*');
    }
}
