<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Filament\Resources\KajianSeries\KajianSeriesResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\KajianSeries;
use App\Models\KajianSession;
use App\Models\Post;
use App\Models\User;
use App\Services\KajianSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/** Tes regresi untuk temuan audit 12 Oktober 2026. */
class AuditHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function as(string $role): User
    {
        $u = User::where('role', $role)->firstOrFail();
        $this->actingAs($u);

        return $u;
    }

    // Temuan 1 (kritis): editor bisa membuat akun lewat URL langsung.
    public function test_editor_tidak_bisa_membuat_atau_mengubah_akun(): void
    {
        $this->as('editor');
        $this->assertFalse(UserResource::canCreate());
        $this->assertFalse(UserResource::canEdit(User::where('role', 'admin')->first()));
        $this->get('/admin/users/create')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
    }

    public function test_admin_tidak_bisa_menghapus_akunnya_sendiri(): void
    {
        $admin = $this->as('admin');
        $this->assertFalse(UserResource::canDelete($admin));
        $this->assertTrue(UserResource::canDelete(User::where('role', 'editor')->first()));
    }

    // Temuan 2 (tinggi): editor bisa menghapus seri kajian beserta seluruh pertemuannya.
    public function test_hanya_admin_yang_bisa_menghapus_seri_kajian(): void
    {
        $series = KajianSeries::first();
        $this->as('editor');
        $this->assertFalse(KajianSeriesResource::canDelete($series));
        $this->as('admin');
        $this->assertTrue(KajianSeriesResource::canDelete($series));
    }

    // Temuan 3 (sedang): pembatalan di tanggal terakhir rentang tidak terbaca.
    public function test_pembatalan_di_batas_akhir_rentang_terbaca(): void
    {
        $s = KajianSeries::where('code', 'fikih')->first();
        KajianSession::create(['kajian_series_id' => $s->id, 'date' => '2026-10-22', 'status' => 'dibatalkan', 'status_reason' => 'uji']);
        $occ = app(KajianSchedule::class)->occurrences(CarbonImmutable::parse('2026-10-16'), CarbonImmutable::parse('2026-10-22'));
        $o = $occ->firstWhere('id', 'fikih-20261022');
        $this->assertSame('dibatalkan', $o['status_override']);
    }

    public function test_log_audit_mencatat_perubahan_dan_tidak_bisa_diubah(): void
    {
        $editor = $this->as('editor');
        $post = Post::create(['type' => 'berita', 'title' => 'Uji audit', 'status' => PostStatus::Draf, 'author_id' => $editor->id]);
        $post->update(['title' => 'Uji audit diubah']);

        $log = AuditLog::where('auditable_type', 'Post')->where('auditable_id', $post->id)->where('action', 'updated')->first();
        $this->assertNotNull($log);
        $this->assertSame($editor->id, $log->user_id);
        $this->assertSame(['Uji audit', 'Uji audit diubah'], $log->changes['title']);

        $this->expectException(LogicException::class);
        $log->update(['action' => 'deleted']);
    }

    public function test_log_audit_tidak_menyimpan_kata_sandi(): void
    {
        $this->as('admin');
        $u = User::where('role', 'editor')->first();
        $u->update(['password' => 'kata-sandi-baru-123']);
        $this->assertFalse(AuditLog::where('auditable_type', 'User')->where('auditable_id', $u->id)->get()
            ->contains(fn ($l) => array_key_exists('password', $l->changes ?? [])));
    }

    public function test_log_audit_hanya_untuk_admin(): void
    {
        $this->as('editor');
        $this->get('/admin/audit-logs')->assertForbidden();
        $this->flushSession(); // AuthenticateSession menolak sesi yang berpindah pengguna di tengah jalan.
        $this->as('admin');
        $this->get('/admin/audit-logs')->assertOk();
    }

    public function test_agenda_dan_prefill_pembatalan(): void
    {
        $this->as('editor');
        $this->get('/admin/agenda')->assertOk()->assertSee('Batalkan');
        $id = KajianSeries::where('code', 'fikih')->value('id');
        $this->get('/admin/kajian-sessions/create?series='.$id.'&date=2026-10-15&status=dibatalkan')->assertOk();
    }

    public function test_pertemuan_ganda_ditolak_dengan_pesan_bukan_error_500(): void
    {
        $this->as('editor');
        $s = KajianSeries::where('code', 'fikih')->first();
        KajianSession::create(['kajian_series_id' => $s->id, 'date' => '2026-10-15', 'status' => 'terjadwal']);
        \Livewire\Livewire::test(\App\Filament\Resources\KajianSessions\Pages\CreateKajianSession::class)
            ->fillForm(['kajian_series_id' => $s->id, 'date' => '2026-10-15', 'status' => 'terjadwal', 'source' => 'admin'])
            ->call('create')
            ->assertHasFormErrors(['date']);
        $this->assertSame(1, KajianSession::where('kajian_series_id', $s->id)->whereDate('date', '2026-10-15')->count());
    }

    public function test_header_keamanan_terpasang(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->get('/admin/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_sitemap_jsonld_dan_404(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->assertSee('/kajian/', false);
        $this->get('/')->assertSee('"@type":"Mosque"', false);
        $this->get('/kajian/tafsir/20261012')->assertSee('"@type":"Event"', false);
        $this->get('/kajian/tidak-ada/20261012')->assertNotFound()->assertSee('Halaman tidak ditemukan');
    }

    public function test_pembatalan_tampil_sebagai_event_cancelled(): void
    {
        $s = KajianSeries::where('code', 'fikih')->first();
        KajianSession::create(['kajian_series_id' => $s->id, 'date' => '2026-10-15', 'status' => 'dibatalkan', 'status_reason' => 'Pemateri safar']);
        $this->get('/kajian/fikih/20261015')->assertOk()->assertSee('Pertemuan ini dibatalkan')->assertSee('Pemateri safar')->assertSee('EventCancelled');
    }
}
