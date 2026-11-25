<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pengaturan situs (tersimpan di cache) tersedia untuk semua tampilan publik sebagai $site.
        \Illuminate\Support\Facades\View::composer(['layouts.site', 'site.*', 'errors.*'], fn ($view) => $view->with('site', \App\Models\Setting::site()));
        }
}
