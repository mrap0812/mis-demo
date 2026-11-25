<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::controller(SiteController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/kajian', 'kajian')->name('kajian');
    Route::get('/kajian/{code}/{date}', 'kajianShow')->name('kajian.show')->where(['code' => '[a-z0-9-]+', 'date' => '\d{8}']);
    Route::get('/berita', 'berita')->name('berita');
    Route::get('/berita/{slug}', 'beritaShow')->name('berita.show');
    Route::get('/profil', 'profil')->name('profil');
    Route::get('/halaman/{slug}', 'halaman')->name('halaman')->where('slug', '[a-z0-9-]+');
    Route::get('/layanan', 'layanan')->name('layanan');
    Route::get('/cari', 'cari')->name('cari')->middleware('throttle:60,1');
    Route::get('/program', 'program')->name('program');
    Route::get('/program/{slug}', 'programShow')->name('program.show')->where('slug', '[a-z0-9-]+');
    Route::get('/infak', 'infak')->name('infak');
    Route::get('/laporan-keuangan', [\App\Http\Controllers\FinancialReportController::class, 'index'])->name('laporan');
    Route::get('/laporan-keuangan/{period}', [\App\Http\Controllers\FinancialReportController::class, 'show'])->name('laporan.show')->where('period', '\d{4}-\d{2}');
    Route::get('/kontak', 'kontak')->name('kontak');
    Route::get('/jadwal-shalat', 'jadwalShalat')->name('shalat');
    Route::get('/kajian.ics', 'kajianIcs')->name('kajian.ics');
    Route::get('/sitemap.xml', 'sitemap')->name('sitemap');
});

// Penyiapan admin pertama (aktif hanya bila SETUP_TOKEN diisi dan belum ada pengguna).
Route::get('/pasang', [\App\Http\Controllers\SetupController::class, 'show']);
Route::post('/pasang', [\App\Http\Controllers\SetupController::class, 'store'])->middleware('throttle:5,10');

// Alamat lama WordPress mis.id (formulir, tulisan, unggahan). Harus paling akhir.
Route::fallback([SiteController::class, 'legacy']);
