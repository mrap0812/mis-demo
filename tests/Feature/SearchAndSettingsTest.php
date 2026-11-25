<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_pencarian_menemukan_kajian_program_berita_dan_layanan(): void
    {
        $this->get('/cari')->assertOk()->assertSee('Cari di situs')->assertDontSee('hasil untuk');
        $this->get('/cari?q=a')->assertOk()->assertSee('minimal 2 huruf');

        $this->get('/cari?q=tafsir')->assertOk()->assertSee("Tafsir Al-Qur&#039;an", false)->assertSee('noindex', false);
        $this->get('/cari?q=BBQ')->assertOk()->assertSee('/program/bbq', false);
        $this->get('/cari?q=kurban')->assertOk()->assertSee('Berita &amp; artikel', false);
        $this->get('/cari?q=ambulans')->assertOk()->assertSee('/layanan#ambulans', false);
        $this->get('/cari?q=Hafizd')->assertOk()->assertSee('Kajian'); // nama ustadz
        $this->get('/cari?q=zzzzqqq')->assertOk()->assertSee('Tidak ada yang cocok');
        $this->get('/cari?q=%25')->assertOk(); // karakter LIKE tidak merusak kueri
    }

    public function test_judul_ayat_hadits_akun_dan_poster_bisa_diatur(): void
    {
        $this->get('/')->assertSee('Menuntut ilmu secara ilmiah dan terbuka untuk kaum muslimin.')->assertSee('QS. At-Taubah: 18')->assertSee('Rekening infak diverifikasi dua pengurus');

        Setting::put([
            'hero_trust' => ['Keterangan uji'],
            'verse_ref' => 'QS. Al-Mujadilah: 11',
            'info_handle' => '@akunuji', 'info_url' => 'https://www.instagram.com/akunuji/',
            'hadith_id' => '',
            'kajian_poster_alt' => 'Poster uji',
        ]);
        $this->get('/')->assertSee('Keterangan uji')->assertDontSee('Rekening infak diverifikasi')->assertSee('QS. Al-Mujadilah: 11');
        $this->get('/kajian')->assertSee('@akunuji')->assertDontSee('Keutamaan menuntut ilmu')->assertSee('Poster uji');
    }
}
