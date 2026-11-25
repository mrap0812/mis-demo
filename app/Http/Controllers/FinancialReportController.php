<?php

namespace App\Http\Controllers;

use App\Models\FinancialReport;
use Carbon\CarbonImmutable;

/** Laporan keuangan publik: hanya laporan yang sudah disetujui (status terbit). */
class FinancialReportController extends Controller
{
    public function index()
    {
        return $this->render(FinancialReport::query()->published()->latest('period')->first());
    }

    public function show(string $period)
    {
        $date = CarbonImmutable::createFromFormat('!Y-m', $period);
        abort_unless($date, 404);

        return $this->render(FinancialReport::query()->published()->where('period', $date->toDateString())->firstOrFail());
    }

    private function render(?FinancialReport $report)
    {
        $all = FinancialReport::query()->published()->orderBy('period')->get();

        return view('site.laporan', ['report' => $report, 'history' => $all->take(-12), 'archive' => $all->reverse()->values()]);
    }
}
