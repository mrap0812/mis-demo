<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings;
use App\Models\AuditLog;
use App\Models\ContentPage;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Support\SafeHtml;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Pengaturan situs, Profil, Layanan, Kontak. */
class SiteContentTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function as(string $role): User
    {
        $u = User::where('role', $role)->firstOrFail();
        $this->actingAs($u);

        return $u;
    }

    public function test_nomor_wa_diubah_ke_format_internasional(): void
    {
        $this->assertSame('https://wa.me/62811500822', SafeHtml::wa('0811-500-822'));
        $this->assertSame('https://wa.me/628115117776', SafeHtml::wa('+62 811 5117 776'));
        $this->assertNull(SafeHtml::wa(''));
    }

    public function test_pengaturan_hanya_untuk_admin(): void
    {
        foreach (['editor', 'peninjau'] as $role) {
            $this->as($role);
            $this->get('/admin/pengaturan-situs')->assertForbidden();
            $this->flushSession();
        }
        $this->as('admin');
        $this->get('/admin/pengaturan-situs')->assertOk()->assertSee('Simpan pengaturan');
    }

    public function test_simpan_pengaturan_lewat_panel_langsung_tampil_dan_tercatat(): void
    {
        $this->as('admin');
        Filament::setCurrentPanel('admin');
        Livewire::test(SiteSettings::class)
            ->fillForm(['hero_title' => 'Judul uji beranda', 'notice_active' => true, 'notice_text' => 'Kajian Kamis diliburkan', 'notice_tone' => 'alert'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/')->assertSee('Judul uji beranda')->assertSee('Kajian Kamis diliburkan');
        $log = AuditLog::where('auditable_type', 'Setting')->get()->first(fn ($l) => ($l->changes['key'][1] ?? null) === 'hero_title');
        $this->assertNotNull($log);
    }

    public function test_nomor_kontak_tidak_valid_ditolak(): void
    {
        $this->as('admin');
        Filament::setCurrentPanel('admin');
        $contacts = Setting::get('contacts');
        $contacts[0]['number'] = 'javascript:alert(1)';
        Livewire::test(SiteSettings::class)->fillForm(['contacts' => $contacts])->call('save')->assertHasFormErrors();
        Livewire::test(SiteSettings::class)->fillForm(['live_url' => 'http://contoh.test'])->call('save')->assertHasFormErrors(['live_url']);
        $this->assertSame('0811-500-822', Setting::get('contacts')[0]['number']);
    }

    public function test_kunci_tak_dikenal_diabaikan_dan_cache_diperbarui(): void
    {
        Setting::put(['bukan_kunci' => 'x', 'footer_about' => 'Keterangan baru']);
        $this->assertDatabaseMissing('settings', ['key' => 'bukan_kunci']);
        $this->assertSame('Keterangan baru', Setting::get('footer_about'));
    }

    public function test_pita_pengumuman_hilang_setelah_tanggal_berakhir(): void
    {
        Setting::put(['notice_active' => true, 'notice_text' => 'Pengumuman lama', 'notice_until' => now('Asia/Makassar')->subDay()->toDateString()]);
        $this->get('/')->assertDontSee('Pengumuman lama');
        Setting::put(['notice_until' => now('Asia/Makassar')->toDateString()]);
        $this->get('/')->assertSee('Pengumuman lama');
    }

    public function test_halaman_profil_layanan_kontak(): void
    {
        $this->get('/profil')->assertOk()->assertSee('1 Muharram 1435 H')->assertSee('Muhammad Hafizd Anshari')->assertSee('Jejak kegiatan');
        $this->get('/layanan')->assertOk()->assertSee('Pengurusan Jenazah')->assertSee('menunggu penetapan pengurus')->assertSee('https://wa.me/628115117776', false);
        $this->get('/kontak')->assertOk()->assertSee('0811-5020-300')->assertSee('@kajianmisbjm')->assertSee('data-map=', false);
        $this->get('/sitemap.xml')->assertSee('/layanan', false);
    }

    public function test_layanan_nonaktif_tidak_tampil_dan_isi_disanitasi(): void
    {
        Service::where('name', 'Donor Darah')->update(['is_active' => false]);
        Service::where('name', 'Ambulans')->update(['details' => '<p>Syarat jelas</p><script>alert(1)</script>']);
        $this->get('/layanan')->assertDontSee('Donor Darah')->assertSee('Syarat jelas')->assertDontSee('<script>alert(1)', false);
    }

    public function test_halaman_tambahan_dan_draf(): void
    {
        ContentPage::create(['title' => 'Fasilitas Masjid', 'body' => '<p>Isi fasilitas</p>', 'is_published' => true]);
        ContentPage::create(['title' => 'Draf Rahasia', 'body' => '<p>x</p>', 'is_published' => false]);
        $this->get('/halaman/fasilitas-masjid')->assertOk()->assertSee('Isi fasilitas');
        $this->get('/halaman/draf-rahasia')->assertNotFound();
        $this->get('/profil')->assertSee('Fasilitas Masjid')->assertDontSee('Draf Rahasia');
    }

    public function test_slug_profil_tidak_bisa_diganti(): void
    {
        $p = ContentPage::where('slug', 'profil')->first();
        $p->update(['slug' => 'lain']);
        $this->assertSame('profil', $p->fresh()->slug);
    }

    public function test_editor_hanya_bisa_melihat_halaman_dan_layanan(): void
    {
        $page = ContentPage::first();
        $svc = Service::first();
        $this->as('editor');
        $this->get('/admin/halaman')->assertOk();
        $this->get('/admin/halaman/'.$page->id.'/edit')->assertForbidden();
        $this->get('/admin/layanan/'.$svc->id.'/edit')->assertForbidden();
        $this->flushSession();
        $this->as('peninjau');
        $this->get('/admin/halaman/'.$page->id.'/edit')->assertOk();
        $this->get('/admin/layanan/'.$svc->id.'/edit')->assertOk();
        $this->assertFalse(auth()->user()->can('delete', $page));
    }
}
