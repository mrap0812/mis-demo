<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan dasar (spesifikasi §22).
 * Halaman publik mendapat CSP ketat: skrip hanya dari situs sendiri (tanpa skrip inline), iframe hanya pemutar YouTube.
 * Panel admin (Filament/Livewire) dikecualikan karena memakai skrip inline dan eval Alpine.
 */
class SecurityHeaders
{
    public const CSP = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; "
        ."font-src 'self'; connect-src 'self'; frame-src https://www.youtube-nocookie.com; object-src 'none'; "
        ."base-uri 'self'; form-action 'self'; frame-ancestors 'self'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $h = $response->headers;
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin');
        if ($request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000');
        }

        $admin = $request->is(config('mis.admin_path'), config('mis.admin_path').'/*', 'livewire*', 'filament/*');
        if ($admin) {
            $h->set('X-Robots-Tag', 'noindex, nofollow');
        } elseif (str_contains((string) $h->get('Content-Type'), 'text/html') && ! $h->has('Content-Security-Policy')) {
            $h->set('Content-Security-Policy', self::CSP.($request->isSecure() ? '; upgrade-insecure-requests' : ''));
        }

        return $response;
    }
}
