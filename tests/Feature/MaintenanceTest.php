<?php

namespace Tests\Feature;

use App\Filament\Pages\Maintenance;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Support\Maintenance\DatabaseBackup;
use App\Support\Maintenance\Housekeeping;
use App\Support\Maintenance\SystemHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function tearDown(): void
    {
        foreach (glob(DatabaseBackup::dir().'/mis-*') ?: [] as $f) {
            @unlink($f);
        }
        parent::tearDown();
    }

    private function as(string $role): User
    {
        $u = User::where('role', $role)->firstOrFail();
        $this->flushSession(); // AuthenticateSession menolak pergantian pengguna dalam satu sesi
        $this->actingAs($u);

        return $u;
    }

    public function test_hanya_admin_yang_bisa_membuka_pemeliharaan(): void
    {
        foreach (['editor', 'peninjau', 'bendahara'] as $role) {
            $this->as($role);
            $this->get('/admin/pemeliharaan')->assertForbidden();
        }
        $this->as('admin');
        $this->get('/admin/pemeliharaan')->assertOk()->assertSee('Kesehatan sistem')->assertSee('Cadangan database');
    }

    public function test_cadangan_berisi_semua_tabel_dan_tercatat_di_log(): void
    {
        $this->as('admin');
        Livewire::test(Maintenance::class)->callAction('backup')->assertNotified('Cadangan dibuat');

        $file = DatabaseBackup::list()[0] ?? null;
        $this->assertNotNull($file);
        $sql = gzdecode(file_get_contents(DatabaseBackup::dir().'/'.$file['name']));
        foreach (['users', 'posts', 'settings', 'kajian_series', 'audit_logs', 'migrations'] as $t) {
            $this->assertStringContainsStringIgnoringCase("CREATE TABLE \"{$t}\"", str_replace('`', '"', $sql), "Tabel {$t} hilang dari cadangan");
        }
        $this->assertStringContainsString('INSERT INTO `kajian_series`', $sql);
        $this->assertStringNotContainsString('INSERT INTO `sessions`', $sql, 'Sesi login tidak boleh ikut dicadangkan');
        $this->assertTrue(AuditLog::where('auditable_type', 'Sistem')->where('action', 'backup')->exists());
    }

    public function test_optimasi_dan_mode_perbaikan_lewat_panel(): void
    {
        $this->as('admin');
        Livewire::test(Maintenance::class)->callAction('optimize')->assertNotified('Situs dioptimalkan');
        Livewire::test(Maintenance::class)
            ->callAction('maintenance', ['maintenance_active' => true, 'maintenance_title' => 'Tutup sebentar', 'maintenance_text' => 'Sedang dirapikan.', 'maintenance_until' => null])
            ->assertNotified('Mode perbaikan aktif');
        $this->assertTrue(Setting::get('maintenance_active'));
        $this->assertTrue(AuditLog::where('auditable_type', 'Sistem')->where('action', 'maint')->exists());
    }

    public function test_nama_file_unduhan_tidak_bisa_keluar_folder(): void
    {
        $this->assertNull(DatabaseBackup::path('../../.env'));
        $this->assertNull(DatabaseBackup::path('mis-20261013-101010.sql.gz/../x'));
        $this->assertNull(DatabaseBackup::path('mis-20261013-101010.sql.gz')); // format benar tapi file tidak ada
    }

    public function test_prune_menyimpan_cadangan_terbaru_saja(): void
    {
        @mkdir(DatabaseBackup::dir(), 0750, true);
        foreach (range(1, 5) as $i) {
            $f = DatabaseBackup::dir()."/mis-2026101{$i}-000000.sql.gz";
            file_put_contents($f, 'x');
            touch($f, 1_790_000_000 + $i);
        }
        $this->assertSame(3, DatabaseBackup::prune(2));
        $this->assertSame(['mis-20261015-000000.sql.gz', 'mis-20261014-000000.sql.gz'], array_column(DatabaseBackup::list(), 'name'));
    }

    public function test_mode_perbaikan_menutup_situs_tapi_admin_tetap_bisa_masuk(): void
    {
        Setting::put(['maintenance_active' => true, 'maintenance_title' => 'Sedang dirapikan', 'maintenance_until' => null]);

        $this->get('/')->assertStatus(503)->assertSee('Sedang dirapikan')->assertHeader('Retry-After');
        $this->get('/kajian')->assertStatus(503);
        $this->get('/admin/login')->assertOk();

        $this->as('editor');
        $this->get('/')->assertOk(); // pengguna yang sudah masuk bisa memeriksa situs
    }

    public function test_mode_perbaikan_berakhir_sendiri(): void
    {
        Setting::put(['maintenance_active' => true, 'maintenance_until' => now('Asia/Makassar')->subMinute()->format('Y-m-d H:i:s')]);
        $this->get('/')->assertOk();
    }

    public function test_kesehatan_sistem_dan_migrasi(): void
    {
        $checks = collect(SystemHealth::checks())->keyBy('label');
        $this->assertSame('ok', $checks['Database']['status']);
        $this->assertSame('ok', $checks['Migrasi database']['status']);
        $this->assertSame([], Housekeeping::pendingMigrations());
    }

    public function test_pangkas_log_menyisakan_entri_utuh(): void
    {
        $log = storage_path('logs/laravel.log');
        $backup = is_file($log) ? file_get_contents($log) : null;
        $line = '['.now()->format('Y-m-d H:i:s')."] testing.ERROR: contoh error panjang\n".str_repeat('#', 200)."\n";
        file_put_contents($log, str_repeat($line, 15000));
        try {
            $this->assertNotNull(Housekeeping::trimLog(1_000_000, 100_000));
            $this->assertLessThanOrEqual(100_000, filesize($log));
            $this->assertStringStartsWith('[', file_get_contents($log));
            $this->assertNotEmpty(Housekeeping::recentErrors());
        } finally {
            $backup === null ? @unlink($log) : file_put_contents($log, $backup);
        }
    }

    public function test_halaman_pasang_hanya_ada_saat_belum_ada_pengguna(): void
    {
        config(['mis.setup_token' => str_repeat('a', 32)]);
        $this->get('/pasang')->assertNotFound(); // sudah ada pengguna dari seeder

        User::query()->delete();
        config(['mis.setup_token' => null]);
        $this->get('/pasang')->assertNotFound(); // token belum diisi

        config(['mis.setup_token' => 'pendek']);
        $this->get('/pasang')->assertNotFound(); // token terlalu pendek

        config(['mis.setup_token' => $token = 'token-penyiapan-yang-panjang-sekali-123']);
        $this->get('/pasang')->assertOk()->assertSee('Buat akun admin pertama');

        $form = ['name' => 'Admin Masjid', 'email' => 'admin@contoh.test', 'password' => 'kataSandi12345', 'password_confirmation' => 'kataSandi12345'];
        $this->post('/pasang', $form + ['token' => 'salah'])->assertSessionHasErrors('token');
        $this->assertFalse(User::query()->exists());

        $this->post('/pasang', $form + ['token' => $token])->assertRedirect('/admin/login');
        $this->assertSame('admin', User::sole()->role);
        $this->get('/pasang')->assertNotFound(); // hilang setelah dipakai
    }
}
