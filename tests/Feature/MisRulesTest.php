<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Filament\Resources\Users\UserResource;
use App\Models\KajianSeries;
use App\Models\KajianSession;
use App\Models\Post;
use App\Models\User;
use App\Services\KajianSchedule;
use App\Services\PrayerTimes;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MisRulesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    // ---------- Jadwal shalat ----------
    public function test_jadwal_shalat_cocok_dengan_kemenag_17_april_2026(): void
    {
        // Rujukan: Bimas Islam Kemenag (via JawaPos) Banjarmasin 17 Apr 2026: Subuh 05.05, Ashar 15.42, Maghrib 18.25, Isya 19.34.
        $p = (new PrayerTimes)->forDate(CarbonImmutable::parse('2026-04-17'));
        $this->assertSame('05:05', PrayerTimes::hhmm($p['fajr']));
        $this->assertSame('15:42', PrayerTimes::hhmm($p['asr']));
        $this->assertSame('18:25', PrayerTimes::hhmm($p['maghrib']));
        $this->assertSame('19:34', PrayerTimes::hhmm($p['isha']));
        $this->assertLessThanOrEqual(1, abs($p['dhuhr'] - (12 * 60 + 25)));
    }

    // ---------- Aturan jadwal kajian ----------
    public function test_pekan_ke_n_dalam_bulan(): void
    {
        $this->assertSame(1, KajianSeries::weekOfMonth(CarbonImmutable::parse('2026-10-07')));
        $this->assertSame(2, KajianSeries::weekOfMonth(CarbonImmutable::parse('2026-10-10')));
        $this->assertSame(4, KajianSeries::weekOfMonth(CarbonImmutable::parse('2026-10-27')));
        $this->assertSame(5, KajianSeries::weekOfMonth(CarbonImmutable::parse('2026-10-31')));
    }

    public function test_kajian_berkala_selasa_pekan_4_menggantikan_kajian_rutin(): void
    {
        $berkala = KajianSeries::where('code', 'daful-iham')->first();
        $rutin = KajianSeries::where('code', 'akhlak')->first();
        $this->assertTrue($berkala->occursOn(CarbonImmutable::parse('2026-10-27')));
        $this->assertFalse($rutin->occursOn(CarbonImmutable::parse('2026-10-27')));
        $this->assertFalse($berkala->occursOn(CarbonImmutable::parse('2026-10-13')));
        $this->assertTrue($rutin->occursOn(CarbonImmutable::parse('2026-10-13')));
    }

    public function test_kajian_muslimah_hanya_sabtu_pekan_1_dan_2(): void
    {
        $s = KajianSeries::where('code', 'muslimah-adab')->first();
        $this->assertTrue($s->occursOn(CarbonImmutable::parse('2026-10-03')));
        $this->assertTrue($s->occursOn(CarbonImmutable::parse('2026-10-10')));
        $this->assertFalse($s->occursOn(CarbonImmutable::parse('2026-10-17')));
    }

    public function test_slot_tentatif_tidak_menjadi_pertemuan(): void
    {
        $occ = app(KajianSchedule::class)->occurrences(CarbonImmutable::parse('2026-10-16'), CarbonImmutable::parse('2026-10-17'));
        $this->assertFalse($occ->contains(fn ($o) => $o['series']->is_tentative));
    }

    public function test_judul_poster_dan_pembatalan_satu_pertemuan_tanpa_mengubah_seri(): void
    {
        $sched = app(KajianSchedule::class);
        $o = $sched->find('tafsir', '2026-10-12');
        $this->assertSame('Tafsir Surah At-Taghabun', $o['title']);
        $this->assertSame('poster', $o['source']);

        $fikih = KajianSeries::where('code', 'fikih')->first();
        KajianSession::create(['kajian_series_id' => $fikih->id, 'date' => '2026-10-15', 'status' => 'dibatalkan', 'status_reason' => 'Pemateri berhalangan']);
        $this->assertSame('dibatalkan', KajianSchedule::status($sched->find('fikih', '2026-10-15')));
        $this->assertSame('terjadwal', KajianSchedule::status($sched->find('fikih', '2026-10-22'), CarbonImmutable::parse('2026-10-20', KajianSchedule::TZ)));
    }

    // ---------- Alur konten dan hak akses ----------
    public function test_editor_tidak_bisa_menerbitkan(): void
    {
        $editor = $this->user('editor');
        $post = Post::create(['type' => 'artikel', 'title' => 'Uji editor', 'status' => PostStatus::Ditinjau, 'author_id' => $editor->id]);
        $this->assertFalse($editor->can('publish', $post));
        $this->assertFalse($editor->can('review', $post));
    }

    public function test_peninjau_tidak_bisa_menyetujui_tulisannya_sendiri(): void
    {
        $peninjau = $this->user('peninjau');
        $own = Post::create(['type' => 'artikel', 'title' => 'Tulisan peninjau', 'status' => PostStatus::Ditinjau, 'author_id' => $peninjau->id]);
        $other = Post::create(['type' => 'artikel', 'title' => 'Tulisan editor', 'status' => PostStatus::Ditinjau, 'author_id' => $this->user('editor')->id]);
        $this->assertFalse($peninjau->can('publish', $own));
        $this->assertTrue($peninjau->can('publish', $other));
    }

    public function test_editor_tidak_bisa_mengubah_tulisan_yang_sedang_ditinjau(): void
    {
        $editor = $this->user('editor');
        $post = Post::create(['type' => 'berita', 'title' => 'Sedang ditinjau', 'status' => PostStatus::Ditinjau, 'author_id' => $editor->id]);
        $this->assertFalse($editor->can('update', $post));
    }

    public function test_draf_tidak_bocor_ke_publik(): void
    {
        $post = Post::create(['type' => 'berita', 'title' => 'Draf rahasia uji', 'status' => PostStatus::Draf, 'author_id' => $this->user('editor')->id]);
        $this->get('/berita')->assertOk()->assertDontSee('Draf rahasia uji');
        $this->get('/berita/'.$post->slug)->assertNotFound();
    }

    public function test_tulisan_terjadwal_belum_tampil_sebelum_waktunya(): void
    {
        Post::create(['type' => 'berita', 'title' => 'Terbit besok uji', 'status' => PostStatus::Terbit, 'published_at' => now()->addDay()]);
        $this->get('/berita')->assertDontSee('Terbit besok uji');
    }

    public function test_menu_pengguna_hanya_untuk_admin(): void
    {
        $this->actingAs($this->user('editor'));
        $this->assertFalse(UserResource::canViewAny());
        $this->actingAs($this->user('admin'));
        $this->assertTrue(UserResource::canViewAny());
    }

    public function test_isi_artikel_disanitasi(): void
    {
        $p = new Post(['body' => '<p>Aman</p><script>alert(1)</script><a href="javascript:alert(1)">x</a>']);
        $html = $p->safeBody();
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('<p>Aman</p>', $html);
    }

    // ---------- Halaman publik ----------
    public function test_halaman_publik_dapat_dibuka(): void
    {
        $this->get('/')->assertOk()->assertSee('Kajian hari ini')->assertSee('Tentang masjid');
        $this->get('/kajian')->assertOk()->assertSee('Kitab yang sedang dikaji');
        $this->get('/kajian/tafsir/20261012')->assertOk()->assertSee('Tafsir Surah At-Taghabun');
        $this->get('/kajian/tafsir/20261013')->assertNotFound(); // Selasa bukan jadwal tafsir
        $this->get('/berita')->assertOk()->assertSee('Berkurban 32 sapi');
    }

    public function test_isi_impor_memakai_host_berjalan_dan_gambar_pembuka_tidak_ganda(): void
    {
        $p = new Post(['body' => '<p></p><figure class="wp-block-image"><img src="http://127.0.0.1:8010/storage/wp/a.jpg" alt=""/></figure><p>Isi</p>']);
        config(['filesystems.disks.public.url' => 'https://mis.id/storage']);
        \Illuminate\Support\Facades\Storage::forgetDisk('public');
        $this->assertStringNotContainsString('127.0.0.1', $p->safeBody());
        $this->assertStringContainsString('https://mis.id/storage/wp/a.jpg', $p->safeBody());
        $this->assertStringNotContainsString('<img', $p->safeBody(true));
        $this->assertStringContainsString('<p>Isi</p>', $p->safeBody(true));
    }
}
