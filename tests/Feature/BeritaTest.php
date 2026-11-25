<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeritaTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_pencarian_dan_halaman_berbahasa_indonesia(): void
    {
        foreach (range(1, 8) as $i) {
            Post::create(['type' => 'berita', 'title' => "Kabar uji $i", 'body' => '<p>isi</p>', 'status' => PostStatus::Terbit, 'published_at' => now()->subDays($i)]);
        }
        $this->get('/berita')->assertOk()->assertSee('Berikutnya')->assertDontSee('Showing')->assertDontSee('Previous');
        $this->get('/berita?q=MIS-Peduli')->assertOk()->assertSee('1 tulisan cocok', false)->assertSee('Penyerahan bantuan MIS-Peduli');
        $this->get('/berita?q=tidakadakatainixyz')->assertOk()->assertSee('Tidak ada tulisan yang cocok');
        $this->get('/berita?q=%25')->assertOk(); // karakter wildcard tidak membobol pencarian
        $this->get('/berita?jenis=bukan-jenis')->assertOk();
    }

    public function test_artikel_bagikan_navigasi_dan_pratinjau_tautan(): void
    {
        $p = Post::published()->latest('published_at')->skip(1)->first();
        $this->get('/berita/'.$p->slug)->assertOk()
            ->assertSee('menit baca')->assertSee('Bagikan')->assertSee('https://wa.me/?text=', false)
            ->assertSee('Sebelumnya')->assertSee('Berikutnya')->assertSee('Berita lainnya')
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('og:description', false)->assertSee('rel="canonical"', false);
    }

    public function test_youtube_jadi_pemutar_ringan_tanpa_memuat_youtube(): void
    {
        $p = Post::create(['type' => 'berita', 'title' => 'Video uji', 'status' => PostStatus::Terbit, 'published_at' => now(),
            'body' => '<p>Simak:</p><figure class="wp-block-embed"><div class="wp-block-embed__wrapper"><a href="https://www.youtube.com/watch?v=pcadji9MS9o">Tonton videonya di YouTube</a></div></figure><p><a href="https://www.youtube.com/watch?v=bad">x</a></p>']);
        $html = $this->get('/berita/'.$p->slug)->assertOk()->getContent();
        $this->assertStringContainsString('data-yt="pcadji9MS9o"', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringNotContainsString('data-yt="bad"', $html);
        $this->assertSame(1, $p->readingMinutes());
    }
}
