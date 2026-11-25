<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Support\Img;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageVariantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_versi_kecil_dibuat_saat_simpan_dan_dipakai_di_srcset(): void
    {
        Storage::fake('public');
        $im = imagecreatetruecolor(1600, 1000);
        ob_start(); imagejpeg($im); Storage::disk('public')->put('programs/uji.jpg', ob_get_clean());

        $this->assertSame('', Img::attrs('programs/uji.jpg', '100vw')); // belum ada versi kecil: pakai asli saja

        Program::create(['title' => 'Uji Gambar', 'summary' => 'x', 'poster' => 'programs/uji.jpg', 'is_published' => true]);

        foreach (Img::WIDTHS as $w) {
            Storage::disk('public')->assertExists(Img::variant('programs/uji.jpg', $w));
        }
        $attrs = Img::attrs('programs/uji.jpg', '120px');
        $this->assertStringContainsString('-480.', $attrs);
        $this->assertStringContainsString('1600w', $attrs);
        $this->get('/program/uji-gambar')->assertOk()->assertSee('srcset=', false);
    }

    public function test_gambar_kecil_tidak_diperbesar(): void
    {
        Storage::fake('public');
        $im = imagecreatetruecolor(200, 150);
        ob_start(); imagepng($im); Storage::disk('public')->put('kecil.png', ob_get_clean());
        $this->assertSame(0, Img::make('kecil.png'));

        // Gambar 400 px hanya mendapat versi 240 px; versi yang lebih lebar dari aslinya tidak dibuat.
        ob_start(); imagepng(imagecreatetruecolor(400, 300)); Storage::disk('public')->put('sedang.png', ob_get_clean());
        $this->assertSame(1, Img::make('sedang.png'));
        $this->assertStringContainsString('-240.', Img::srcset('sedang.png'));
        $this->assertStringNotContainsString('-480.', Img::srcset('sedang.png'));
        $this->assertSame(0, Img::make('../../.env'));
    }
}
