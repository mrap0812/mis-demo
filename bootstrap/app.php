<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->web(append: [\App\Http\Middleware\SiteMaintenance::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

/*
 * Paket cPanel: folder "public" Laravel diletakkan sebagai public_html, bersebelahan dengan folder aplikasi
 * (/home/akun/mis-app dan /home/akun/public_html). Berlaku untuk web maupun perintah artisan/cron.
 */
if (! is_dir($app->basePath('public')) && is_dir($webroot = dirname($app->basePath()).'/public_html')) {
    $app->usePublicPath($webroot);
}

return $app;
