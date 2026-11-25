<?php

/*
 * Pintu masuk situs (paket cPanel). Kode aplikasi ada di ../mis-app, di luar folder web,
 * sehingga file .env, database, dan kode tidak bisa dibuka dari browser.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appDir = __DIR__.'/../mis-app';

if (file_exists($maintenance = $appDir.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appDir.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appDir.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
