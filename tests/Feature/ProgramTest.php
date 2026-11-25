<?php

namespace Tests\Feature;

use App\Filament\Resources\Programs\ProgramResource;
use App\Models\Program;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_daftar_dan_halaman_program_dari_poster(): void
    {
        $this->get('/program')->assertOk()->assertSee('Sedekah Sampah')->assertSee("Belajar Baca Qur&#039;an (BBQ)", false);

        $this->get('/program/sedekah-sampah')->assertOk()
            ->assertSee('Sampah yang diterima')->assertSee('Minyak bekas')
            ->assertSee('Setiap Ahad, 08.00 sampai 11.00 WITA')
            ->assertSee('Hasilnya digunakan untuk operasional masjid')
            ->assertSee('https://wa.me/62811500822', false)
            ->assertSee('Sumber: Poster resmi Sedekah Sampah');

        $this->get('/program/bbq')->assertOk()
            ->assertSee('BBQ Iqro')->assertSee('BBQ Tahsin')
            ->assertSee('Muslim laki-laki, usia minimal 14 tahun')
            ->assertSee('Gratis')->assertSee('https://wa.me/62811501145', false)
            ->assertSee('YP Al Umm Banjarmasin')->assertSee('https://www.instagram.com/ypalumm/', false);

        $this->get('/kontak')->assertSee('Jl. AMD XII, Pemurus Dalam');
    }

    public function test_umkm_mis_dari_poster_dan_masuk_lewat_migrasi(): void
    {
        $this->assertSame(1, Program::where('slug', 'umkm-mis')->count()); // migrasi + seeder tidak menggandakan
        $this->get('/program/umkm-mis')->assertOk()
            ->assertSee('Setiap Sabtu, mulai pukul 10.00 pagi')->assertSee('Menu tersedia')->assertSee('thai tea')
            ->assertSee('Kemuslimahan Masjid Imam Syafi&#039;i Banjarmasin', false)
            ->assertSee('https://wa.me/628115156464', false)
            ->assertSee('Ekonomi &amp; UMKM', false);
    }

    public function test_penanda_hari_ini_dan_besok(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-17 08:00', 'Asia/Makassar')); // Sabtu
        $this->get('/')->assertOk()->assertSee('Hari ini juga di masjid')->assertSee('Stand Jualan UMKM MIS');
        $html = $this->get('/program')->assertOk()->assertSee('Hari ini')->assertSee('Besok')->getContent();
        $this->assertLessThan(strpos($html, 'Sedekah Sampah'), strpos($html, 'Stand Jualan UMKM MIS'), 'Program hari ini tampil paling atas');
        $this->get('/program/bbq')->assertSee('Besok');

        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-14 08:00', 'Asia/Makassar')); // Rabu
        $this->get('/')->assertDontSee('Hari ini juga di masjid');
    }

    public function test_program_draf_tidak_tampil(): void
    {
        Program::create(['title' => 'Program Uji', 'summary' => 'x', 'is_published' => false]);
        $this->get('/program/program-uji')->assertNotFound();
        $this->get('/program')->assertDontSee('Program Uji');
    }

    public function test_beranda_menu_dan_sitemap_memuat_program(): void
    {
        $this->get('/')->assertOk()->assertSee('Program masjid')->assertSee('/program/bbq', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/program/sedekah-sampah', false);
    }

    public function test_layanan_tahsin_terhubung_ke_bbq(): void
    {
        $s = Service::where('name', 'Belajar Tahsin')->first();
        $this->assertSame('tersedia', $s->status);
        $this->get('/layanan')->assertSee('/program/bbq', false);
    }

    public function test_hanya_peninjau_dan_admin_yang_bisa_mengubah_program(): void
    {
        $this->actingAs(User::where('role', 'editor')->first());
        $this->assertFalse(ProgramResource::canCreate());
        $this->get('/admin/program')->assertOk();
        $this->get('/admin/program/create')->assertForbidden();

        $this->flushSession();
        $this->actingAs(User::where('role', 'peninjau')->first());
        $this->get('/admin/program/'.Program::where('slug', 'bbq')->value('id').'/edit')->assertOk()->assertSee('Pilihan kelas');
    }

    public function test_beranda_jadwal_sepekan_mulai_hari_ini_dan_seo(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-14 08:00', 'Asia/Makassar')); // Rabu
        $html = $this->get('/')->assertOk()->getContent();
        preg_match_all('/<div class="tt-day[^"]*">\s*<h3>(\w+)/', $html, $m);
        $this->assertSame(['Rabu', 'Kamis'], $m[1]); // beranda: hari ini dan besok saja
        $this->assertStringContainsString('<title>Masjid Imam Syafi&#039;i Banjarmasin | Kajian Ilmiah Gratis &amp; Jadwal Shalat', $html);
        $this->assertStringContainsString('"@type":"SearchAction"', $html);
        $this->assertStringContainsString('"@type":"Mosque"', $html);

        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-18 08:00', 'Asia/Makassar')); // Ahad
        preg_match_all('/<div class="tt-day[^"]*">\s*<h3>(\w+)/', $this->get('/')->getContent(), $m);
        $this->assertSame('Senin', $m[1][0]);
    }

    public function test_warna_kartu_kajian_selalu_dalam_palet_situs(): void
    {
        $this->assertSame([], \App\Models\KajianSeries::query()->whereNotIn('color', array_keys(\App\Models\KajianSeries::TONES))->pluck('color')->all());
        $s = \App\Models\KajianSeries::query()->first();
        $s->update(['color' => '#2F6B4F']); // hijau lama
        $this->assertArrayHasKey($s->fresh()->color, \App\Models\KajianSeries::TONES);
        $this->assertSame('#A3121A', \App\Models\KajianSeries::tone('bukan-warna'));
        $css = file_get_contents(public_path('css/site.css'));
        $this->assertDoesNotMatchRegularExpression('/#(177E44|2F6B4F|5E8C3A|D9541A|24527A|1F6B3A)/i', $css, 'Warna di luar palet merah-emas-putih');
        preg_match_all('#url\((/images/[^)]+)\)#', $css, $m);
        $this->assertNotEmpty($m[1]);
        foreach (array_unique($m[1]) as $u) {
            $this->assertFileExists(public_path(ltrim($u, '/')), "Motif {$u} tidak ada");
        }
    }

    public function test_agenda_dilipat_dan_404_membantu(): void
    {
        $this->get('/kajian')->assertOk()->assertSee('Tampilkan 7 hari berikutnya');
        $this->get('/kajian?q=tafsir')->assertOk()->assertDontSee('Tampilkan 7 hari berikutnya');
        $this->get('/halaman-yang-tidak-ada')->assertNotFound()->assertSee('action="'.route('cari').'"', false)->assertSee('Jadwal kajian');
    }
}
