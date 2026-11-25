<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Migrasi dari WordPress mis.id: isi lengkap, gambar, dan alamat lama tetap berfungsi. */
class WordPressMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->dir = sys_get_temp_dir().'/mis-wp-'.uniqid();
        mkdir($this->dir.'/uploads/2023/09', 0777, true);
        $img = imagecreatetruecolor(2400, 1200);
        imagejpeg($img, $this->dir.'/uploads/2023/09/foto.jpg');
        file_put_contents($this->dir.'/uploads/2023/09/buletin.pdf', '%PDF-1.4 uji');
        file_put_contents($this->dir.'/posts.json', json_encode([
            ['wp_id' => '1', 'slug' => 'mis-peduli-01', 'status' => 'publish', 'title' => 'Judul WP', 'date' => '2023-09-11 13:37:00', 'categories' => ['Berita Kegiatan'],
                'excerpt' => '', 'body' => '<p>Isi lengkap dari WordPress.</p><img src="https://www.mis.id/wp-content/uploads/2023/09/foto.jpg">', 'cover' => '2023/09/foto.jpg', 'files' => ['2023/09/foto.jpg']],
            ['wp_id' => '2', 'slug' => 'buletin-baru', 'status' => 'publish', 'title' => 'BULETIN JUMAT PEKAN INI', 'date' => '2021-12-13 08:00:00', 'categories' => ['Artikel'],
                'excerpt' => '', 'body' => '<p><a href="https://www.mis.id/wp-content/uploads/2023/09/buletin.pdf">Unduh Buletin (PDF)</a></p>', 'cover' => null, 'files' => ['2023/09/buletin.pdf']],
            ['wp_id' => '3', 'slug' => '', 'status' => 'draft', 'title' => 'Draf lama', 'date' => '2023-08-02 10:00:00', 'categories' => ['Artikel'],
                'excerpt' => '', 'body' => '<p>Belum terbit.</p>', 'cover' => null, 'files' => []],
        ]));
    }

    private function import(): void
    {
        $this->artisan('mis:import-wordpress', ['json' => $this->dir.'/posts.json', 'uploads' => $this->dir.'/uploads'])->assertSuccessful();
    }

    public function test_impor_isi_gambar_dan_pdf(): void
    {
        $before = Post::count();
        $this->import();
        $this->import(); // dijalankan ulang: tidak dobel

        $this->assertSame($before + 2, Post::count());
        $existing = Post::where('source_url', 'https://www.mis.id/mis-peduli-01/')->first();
        $this->assertStringContainsString('Isi lengkap dari WordPress.', $existing->body);
        $this->assertStringContainsString('/storage/wp/2023/09/foto.jpg', $existing->body);
        $this->assertStringNotContainsString('Judul WP', $existing->title, 'Judul yang sudah dirapikan tidak ditimpa');

        $new = Post::where('source_url', 'https://www.mis.id/buletin-baru/')->first();
        $this->assertSame('Buletin jumat pekan ini', $new->title);
        $this->assertSame(PostStatus::Terbit, $new->status);
        $this->assertSame('artikel', $new->type);
        Storage::disk('public')->assertExists('wp/2023/09/buletin.pdf');
        $this->assertSame(1600, getimagesize(Storage::disk('public')->path('wp/2023/09/foto.jpg'))[0], 'Foto besar diperkecil');

        $draft = Post::where('source_url', 'https://www.mis.id/?p=3')->first();
        $this->assertSame(PostStatus::Draf, $draft->status);
        $this->get('/berita/'.$draft->slug)->assertNotFound();
    }

    public function test_alamat_lama_tetap_berfungsi(): void
    {
        $this->import();
        $post = Post::where('source_url', 'https://www.mis.id/mis-peduli-01/')->first();
        $this->get('/mis-peduli-01')->assertRedirect(route('berita.show', $post->slug))->assertStatus(301);
        $this->get('/mis-peduli-01/')->assertStatus(301);
        $this->get('/donor-darah')->assertStatus(302)->assertRedirectContains('docs.google.com/forms');
        $this->get('/beranda-masjid')->assertRedirect('/');
        $this->get('/wp-content/uploads/2023/09/foto.jpg')->assertStatus(301);
        $this->get('/alamat-yang-tidak-pernah-ada')->assertNotFound()->assertSee('Halaman tidak ditemukan');
        $this->get('/wp-login.php')->assertNotFound();
    }
}
