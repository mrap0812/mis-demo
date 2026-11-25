<?php

namespace Tests\Feature;

use App\Filament\Resources\FinancialReports\Pages\ListFinancialReports;
use App\Models\FinancialReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function as(string $role): User
    {
        $u = User::where('role', $role)->firstOrFail();
        $this->flushSession();
        $this->actingAs($u);

        return $u;
    }

    public function test_laporan_oktober_dari_poster_konsisten_dan_tampil(): void
    {
        $r = FinancialReport::sole();
        $this->assertSame(49288715, $r->incomeTotal());
        $this->assertSame(47212870, $r->expenseTotal());
        $this->assertSame(43304255, $r->closingBalance()); // sama dengan poster resmi

        $this->get('/laporan-keuangan')->assertOk()->assertSee('Oktober 2026')->assertSee('Rp 43.304.255')->assertSee('Mukafaah ustadz');
        $this->get('/laporan-keuangan/2026-10')->assertOk();
        $this->get('/laporan-keuangan/2026-09')->assertNotFound();
        $this->get('/infak')->assertSee(route('laporan'), false);
    }

    public function test_draf_tidak_tampil_dan_perubahan_angka_menarik_kembali_terbitan(): void
    {
        FinancialReport::create(['period' => '2026-11-15', 'opening_balance' => 43304255, 'incomes' => [['label' => 'Infak tunai', 'amount' => 1000]], 'expenses' => []]);
        $this->get('/laporan-keuangan/2026-11')->assertNotFound();
        $this->assertSame('2026-11-01', FinancialReport::latest('period')->first()->period->toDateString());

        $okt = FinancialReport::where('period', '2026-10-01')->first();
        $okt->update(['opening_balance' => 1]);
        $this->assertSame('draf', $okt->fresh()->status);
        $this->get('/laporan-keuangan/2026-10')->assertNotFound();
    }

    public function test_persetujuan_wajib_oleh_pengurus_lain(): void
    {
        $nov = FinancialReport::create(['period' => '2026-11-01', 'opening_balance' => 43304255, 'incomes' => [['label' => 'Infak', 'amount' => 5000]], 'expenses' => []]);
        $this->assertSame(43304255 + 5000, $nov->closingBalance());
        $this->assertSame(0, $nov->openingGap());

        $bendahara = $this->as('bendahara');
        $this->get('/'.config('mis.admin_path').'/laporan-keuangan')->assertOk();
        Livewire::test(ListFinancialReports::class)->callTableAction('submit', $nov);
        $this->assertSame('diajukan', $nov->fresh()->status);
        $this->assertFalse($bendahara->can('approve', $nov->fresh()));

        $this->as('editor');
        $this->get('/'.config('mis.admin_path').'/laporan-keuangan')->assertForbidden();

        $this->as('peninjau');
        Livewire::test(ListFinancialReports::class)->callTableAction('approve', $nov);
        $this->assertSame('terbit', $nov->fresh()->status);
        $this->get('/laporan-keuangan')->assertSee('November 2026');
    }
}
