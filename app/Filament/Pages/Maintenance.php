<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Support\Maintenance\DatabaseBackup;
use App\Support\Maintenance\Housekeeping;
use App\Support\Maintenance\MaintenanceMode;
use App\Support\Maintenance\SystemHealth;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use UnitEnum;

/**
 * Pemeliharaan server tanpa SSH: cek kesehatan, cadangan database, migrasi, optimasi, mode perbaikan.
 * Hanya admin. Setiap tindakan tercatat di Log aktivitas (jenis "Sistem").
 */
class Maintenance extends Page
{
    protected string $view = 'filament.pages.maintenance';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Situs';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'Pemeliharaan';

    protected static ?string $slug = 'pemeliharaan';

    /** Hitung ukuran folder hanya bila diminta (bisa lambat pada folder besar). */
    public bool $showSizes = false;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected function getViewData(): array
    {
        $checks = SystemHealth::checks();

        return [
            'checks' => $checks,
            'summary' => SystemHealth::summary($checks),
            'backups' => DatabaseBackup::list(),
            'pending' => Housekeeping::pendingMigrations(),
            'logErrors' => Housekeeping::recentErrors(),
            'sizes' => $this->showSizes ? Housekeeping::folderSizes() : null,
            'maintenance' => MaintenanceMode::active(),
            'site' => Setting::site(),
        ];
    }

    private function guard(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    private function fail(string $title, Throwable $e): void
    {
        report($e);
        Notification::make()->danger()->title($title)->body('Rincian teknis tercatat di log error.')->persistent()->send();
    }

    public function backupAction(): Action
    {
        return Action::make('backup')->label('Buat cadangan sekarang')->icon(Heroicon::OutlinedCircleStack)
            ->action(function () {
                $this->guard();
                try {
                    $path = DatabaseBackup::create();
                    $pruned = DatabaseBackup::prune(config('mis.backup_keep'));
                    AuditLog::system('backup', 'Cadangan database dibuat', ['file' => basename($path)]);
                    Notification::make()->success()->title('Cadangan dibuat')
                        ->body(basename($path).' ('.Housekeeping::bytes(filesize($path)).')'.($pruned ? ". {$pruned} cadangan lama dihapus." : ''))->send();
                } catch (Throwable $e) {
                    $this->fail('Cadangan gagal dibuat', $e);
                }
            });
    }

    public function download(string $name): ?BinaryFileResponse
    {
        $this->guard();
        $path = DatabaseBackup::path($name);
        if (! $path) {
            Notification::make()->danger()->title('File tidak ditemukan')->send();

            return null;
        }
        AuditLog::system('download', 'Cadangan database diunduh', ['file' => $name]);

        return response()->download($path);
    }

    public function deleteBackupAction(): Action
    {
        return Action::make('deleteBackup')->label('Hapus')->icon(Heroicon::OutlinedTrash)->color('danger')->link()
            ->requiresConfirmation()->modalHeading(fn (array $arguments) => 'Hapus '.($arguments['name'] ?? '').'?')
            ->modalDescription('File cadangan ini akan dihapus permanen dari server.')
            ->action(function (array $arguments) {
                $this->guard();
                $path = DatabaseBackup::path($arguments['name'] ?? '');
                if ($path && unlink($path)) {
                    AuditLog::system('deleted', 'Cadangan database dihapus', ['file' => basename($path)]);
                    Notification::make()->success()->title('Cadangan dihapus')->send();
                }
            });
    }

    public function migrateAction(): Action
    {
        return Action::make('migrate')->label('Jalankan migrasi')->icon(Heroicon::OutlinedArrowPath)->color('warning')
            ->requiresConfirmation()->modalHeading('Perbarui struktur database?')
            ->modalDescription('Cadangan otomatis dibuat lebih dulu. Lakukan saat pengunjung sepi.')
            ->modalSubmitActionLabel('Cadangkan & jalankan')
            ->action(function () {
                $this->guard();
                try {
                    $backup = basename(DatabaseBackup::create());
                    $out = Housekeeping::migrate();
                    AuditLog::system('migrate', 'Migrasi database dijalankan', ['cadangan' => $backup, 'hasil' => mb_substr($out, 0, 280)]);
                    Notification::make()->success()->title('Migrasi selesai')->body("Cadangan sebelum migrasi: {$backup}")->send();
                } catch (Throwable $e) {
                    $this->fail('Migrasi gagal. Data aman di cadangan terakhir.', $e);
                }
            });
    }

    public function optimizeAction(): Action
    {
        return Action::make('optimize')->label('Optimalkan')->icon(Heroicon::OutlinedBolt)
            ->tooltip('Simpan konfigurasi, rute, dan tampilan dalam cache agar situs lebih cepat')
            ->action(function () {
                $this->guard();
                AuditLog::system('optimize', 'Cache konfigurasi, rute, tampilan dibuat');
                // config:cache membangun instance aplikasi kedua; bila dijalankan di tengah request, notifikasi dan
                // sesi request ini ikut rusak. Karena itu dijalankan setelah respons terkirim ke peramban.
                app()->terminating(function () {
                    if (app()->runningUnitTests()) {
                        return; // di tes, membangun cache akan mengganti koneksi database tes
                    }
                    try {
                        Artisan::call('optimize');
                    } catch (Throwable $e) {
                        report($e);
                        Artisan::call('optimize:clear');
                    }
                });
                Notification::make()->success()->title('Situs dioptimalkan')
                    ->body('Cache dibuat dalam beberapa detik. Muat ulang halaman ini untuk melihat statusnya.')->send();
            });
    }

    public function clearCacheAction(): Action
    {
        return Action::make('clearCache')->label('Bersihkan cache')->icon(Heroicon::OutlinedSparkles)->color('gray')
            ->tooltip('Pakai setelah mengubah file .env atau mengunggah versi baru')
            ->action(function () {
                $this->guard();
                Artisan::call('optimize:clear');
                AuditLog::system('cache', 'Semua cache dibersihkan');
                Notification::make()->success()->title('Cache dibersihkan')->send();
            });
    }

    public function tidyAction(): Action
    {
        return Action::make('tidy')->label('Rapikan')->icon(Heroicon::OutlinedArchiveBoxXMark)->color('gray')
            ->tooltip('Hapus sesi dan cache kedaluwarsa, pangkas log yang terlalu besar')
            ->action(function () {
                $this->guard();
                $done = Housekeeping::tidy();
                AuditLog::system('tidy', 'Pembersihan rutin', ['hasil' => implode('; ', $done)]);
                Notification::make()->success()->title('Selesai dirapikan')->body(implode('. ', $done).'.')->send();
            });
    }

    public function thumbsAction(): Action
    {
        return Action::make('thumbs')->label('Buat gambar kecil')->icon(Heroicon::OutlinedPhoto)->color('gray')
            ->tooltip('Versi 240 sampai 960 px agar halaman ringan di ponsel. Otomatis untuk unggahan baru; tombol ini untuk gambar lama')
            ->action(function () {
                $this->guard();
                $r = \App\Support\Img::makeAll();
                AuditLog::system('thumbs', 'Versi kecil gambar dibuat', ['hasil' => "{$r['made']} file dari {$r['files']} gambar"]);
                Notification::make()->success()->title('Gambar kecil siap')->body("{$r['files']} gambar diperiksa, {$r['made']} versi kecil dibuat.")->send();
            });
    }

    public function storageLinkAction(): Action
    {
        return Action::make('storageLink')->label('Buat tautan storage')->icon(Heroicon::OutlinedLink)->color('gray')
            ->action(function () {
                $this->guard();
                try {
                    Artisan::call('storage:link');
                    $ok = is_dir(public_path('storage'));
                    AuditLog::system('storage', 'Tautan storage dibuat', ['berhasil' => $ok ? 'ya' : 'tidak']);
                    $ok
                        ? Notification::make()->success()->title('Tautan storage siap')->send()
                        : Notification::make()->warning()->title('Server menolak membuat tautan')->body('Ikuti langkah cadangan di PANDUAN-INSTALASI bagian "Gambar tidak tampil".')->persistent()->send();
                } catch (Throwable $e) {
                    $this->fail('Tautan storage gagal dibuat', $e);
                }
            });
    }

    public function maintenanceAction(): Action
    {
        return Action::make('maintenance')->label('Atur mode perbaikan')->icon(Heroicon::OutlinedNoSymbol)
            ->color(fn () => MaintenanceMode::active() ? 'danger' : 'gray')
            ->fillForm(fn () => array_intersect_key(Setting::site(), array_flip(['maintenance_active', 'maintenance_title', 'maintenance_text', 'maintenance_until'])))
            ->schema([
                Toggle::make('maintenance_active')->label('Aktifkan: pengunjung melihat halaman "sedang diperbarui"')
                    ->helperText('Panel admin tetap bisa dibuka. Pengguna yang sudah masuk tetap melihat situs seperti biasa.'),
                TextInput::make('maintenance_title')->label('Judul')->required()->maxLength(80),
                Textarea::make('maintenance_text')->label('Pesan untuk pengunjung')->required()->rows(3)->maxLength(400),
                DateTimePicker::make('maintenance_until')->label('Selesai otomatis pada (WITA, opsional)')->seconds(false)->native(false)
                    ->timezone('Asia/Makassar')->helperText('Setelah waktu ini situs kembali normal sendiri. Kosongkan bila belum pasti.'),
            ])
            ->modalSubmitActionLabel('Simpan')
            ->action(function (array $data) {
                $this->guard();
                Setting::put($data);
                AuditLog::system('maint', $data['maintenance_active'] ? 'Mode perbaikan diaktifkan' : 'Mode perbaikan dimatikan');
                Notification::make()->success()->title($data['maintenance_active'] ? 'Mode perbaikan aktif' : 'Situs kembali normal')->send();
            });
    }

    public function loadSizes(): void
    {
        $this->guard();
        $this->showSizes = true;
    }

    protected function getHeaderActions(): array
    {
        return [$this->backupAction(), $this->optimizeAction()];
    }
}
