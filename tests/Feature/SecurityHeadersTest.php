<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_halaman_publik_punya_csp_dan_tanpa_skrip_inline(): void
    {
        foreach (['/', '/kajian', '/berita', '/program', '/infak', '/kontak', '/cari?q=tafsir'] as $url) {
            $res = $this->get($url)->assertOk();
            $this->assertStringContainsString("script-src 'self'", (string) $res->headers->get('Content-Security-Policy'), $url);
            $html = $res->getContent();
            // Hanya JSON-LD (data, tidak dieksekusi) yang boleh inline; skrip lain wajib berkas sendiri.
            $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*(src=|application\/ld\+json))[^>]*>/i', $html, "Skrip inline di {$url}");
            $this->assertDoesNotMatchRegularExpression('/\son(click|change|submit|load)=/i', $html, "Handler inline di {$url}");
        }
    }

    public function test_panel_admin_tidak_diberi_csp_publik(): void
    {
        $res = $this->get('/'.config('mis.admin_path').'/login')->assertOk();
        $this->assertNull($res->headers->get('Content-Security-Policy'));
        $this->assertSame('noindex, nofollow', $res->headers->get('X-Robots-Tag'));
    }
}
