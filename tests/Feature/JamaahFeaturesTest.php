<?php

namespace Tests\Feature;

use App\Models\KajianSeries;
use App\Models\KajianSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Jadwal shalat bulanan, kalender langganan kajian, manifest aplikasi. */
class JamaahFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_jadwal_shalat_bulanan(): void
    {
        $r = $this->get('/jadwal-shalat?bulan=2026-10')->assertOk()->assertSee('Jadwal shalat Oktober 2026')->assertSee('Cetak jadwal');
        $this->assertSame(31, substr_count($r->getContent(), '<th scope="row">'));
        $this->get('/jadwal-shalat?bulan=2026-02')->assertOk();
        $this->get('/jadwal-shalat?bulan=bukan-bulan')->assertOk(); // jatuh ke bulan ini
        $this->get('/jadwal-shalat?bulan=2031-01')->assertNotFound(); // di luar 12 bulan
    }

    public function test_jadwal_shalat_cocok_dengan_hitungan_harian(): void
    {
        $t = app(\App\Services\PrayerTimes::class)->forDate(\Carbon\CarbonImmutable::parse('2026-10-15', 'Asia/Makassar'));
        $this->get('/jadwal-shalat?bulan=2026-10')->assertSee(\App\Services\PrayerTimes::hhmm($t['maghrib']));
    }

    public function test_kalender_langganan_kajian(): void
    {
        $s = KajianSeries::where('code', 'fikih')->first();
        $d = now('Asia/Makassar')->next(4)->toDateString(); // Kamis berikutnya
        KajianSession::create(['kajian_series_id' => $s->id, 'date' => $d, 'status' => 'dibatalkan', 'status_reason' => 'Pemateri safar']);

        $r = $this->get('/kajian.ics')->assertOk();
        $this->assertStringStartsWith('text/calendar', $r->headers->get('Content-Type'));
        $body = $r->getContent();
        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $body);
        $this->assertStringContainsString('STATUS:CANCELLED', $body);
        $this->assertStringContainsString('UID:fikih-'.str_replace('-', '', $d).'@mis-banjarmasin', $body);
        foreach (explode("\r\n", $body) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line), 'Baris iCalendar harus dilipat maksimal 75 oktet');
        }
        $this->assertStringNotContainsString('SUMMARY:Khutbah', $this->get('/kajian.ics?jenis=muslimah')->getContent());
        $this->get('/kajian')->assertSee('id="langganan"', false)->assertSee('webcal://', false);
    }

    public function test_manifest_dan_ikon_aplikasi(): void
    {
        $m = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);
        $this->assertSame('standalone', $m['display']);
        foreach ($m['icons'] as $i) {
            $this->assertFileExists(public_path(ltrim($i['src'], '/')));
        }
        $this->get('/')->assertSee('rel="manifest"', false)->assertSee('Lihat jadwal sebulan');
    }
}
