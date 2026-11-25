<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\DonationAccountChange;
use App\Models\Program;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_renovasi_total_sesuai_poster_baru_tanpa_angka_lama(): void
    {
        $c = Campaign::where('slug', 'renovasi-toilet-ikhwan')->sole();
        $this->assertSame('Renovasi Total Toilet Ikhwan', $c->title);
        $this->assertNull($c->target_amount);
        $this->get('/infak')->assertOk()->assertSee('10 pintu toilet')->assertDontSee('Masih diperlukan')->assertDontSee('145.928.999');

        $c->update(['target_amount' => 156000000, 'collected_amount' => 10071001]);   // bila pengurus mengisi angka resmi
        $this->assertSame(145928999, $c->remaining());
        $this->assertSame('6,46', number_format($c->percent(), 2, ',', '.'));
        $this->get('/infak')->assertSee('Masih diperlukan');
    }

    public function test_hitung_mundur_ramadhan_otomatis_dan_hilang_setelah_syawal(): void
    {
        $r = Campaign::where('slug', 'ramadhan-1448')->sole();
        $this->assertSame('2027-02-08', $r->ramadhanStart()->toDateString());   // 1 Ramadhan 1448 menurut Umm al-Qura
        $this->assertSame(77, $r->daysToRamadhan(CarbonImmutable::parse('2026-11-23', 'Asia/Makassar')));

        $this->travelTo(CarbonImmutable::parse('2026-11-23 09:00', 'Asia/Makassar'));
        $this->get('/')->assertOk()->assertSee('Kampanye donasi')->assertSee('77</b> hari menuju 1 Ramadhan 1448 H', false);
        $this->travelTo(CarbonImmutable::parse('2027-03-10 09:00', 'Asia/Makassar'));
        $this->get('/infak')->assertOk()->assertDontSee('Menuju Ramadhan 1448 H')->assertSee('Renovasi Total Toilet Ikhwan');
    }

    public function test_rekening_poster_tampil_dan_rekening_tertaut_menggantikan_setelah_disetujui(): void
    {
        $this->get('/infak')->assertSee('7700 6000 62')->assertSee('Sesuai poster resmi')->assertSee('data-copy="7700600062"', false);
        $this->get('/infak')->assertSee('sedang diverifikasi pengurus'); // kampanye Ramadhan belum punya rekening tampil

        $change = DonationAccountChange::where('payload', 'like', '%7700 6000 62%')->sole();
        $acc = $change->approve(User::where('role', 'bendahara')->firstOrFail(), '7700600062');
        Campaign::where('slug', 'renovasi-toilet-ikhwan')->update(['donation_account_id' => $acc->id]);

        $this->get('/infak')->assertSee('7700 6000 62')->assertDontSee('Sesuai poster resmi');
    }

    public function test_warung_isb_tampil_sebagai_program(): void
    {
        $p = Program::where('slug', 'warung-isb')->sole();
        $this->assertSame('ekonomi', $p->category);
        $this->get('/program/warung-isb')->assertOk()->assertSee('Warung ISB')->assertSee('0877-8788-8122')->assertSee('Rumah Dakwah');
    }
}
