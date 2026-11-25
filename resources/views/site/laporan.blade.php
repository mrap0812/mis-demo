@php $rp = fn ($n) => \App\Models\FinancialReport::rupiah($n); @endphp
@extends('layouts.site', ['title' => $report ? 'Laporan keuangan '.$report->label() : 'Laporan keuangan', 'description' => "Laporan pemasukan dan pengeluaran kas Masjid Imam Syafi'i Banjarmasin setiap bulan."])
@section('content')
<section class="view">
  @include('site.partials.phead', ['crumb' => 'Laporan keuangan', 'ar' => 'التقرير المالي', 'eyebrow' => 'Transparansi', 'h1' => $report ? 'Laporan keuangan '.$report->label() : 'Laporan keuangan', 'lede' => 'Amanah infak jamaah dilaporkan setiap bulan. Laporan tampil di sini setelah diperiksa dua pengurus.'])

  @if(! $report)
    <div class="callout">Belum ada laporan yang diterbitkan.</div>
  @else
  <div class="lk-sum">
    <div><span>Saldo awal</span><b>{{ $rp($report->opening_balance) }}</b></div>
    <div><span>Pemasukan</span><b class="in">+ {{ $rp($report->incomeTotal()) }}</b></div>
    <div><span>Pengeluaran</span><b class="out">&minus; {{ $rp($report->expenseTotal()) }}</b></div>
    <div class="lk-close"><span>Saldo akhir</span><b>{{ $rp($report->closingBalance()) }}</b></div>
  </div>

  <div class="lk-grid">
    @foreach(['incomes' => ['Rincian pemasukan', $report->incomeTotal()], 'expenses' => ['Rincian pengeluaran', $report->expenseTotal()]] as $key => [$title, $total])
    <section class="card lk-table" aria-labelledby="h-{{ $key }}">
      <h2 id="h-{{ $key }}">{{ $title }}</h2>
      <table>
        <tbody>
          @foreach($report->$key as $it)
          <tr><th scope="row">{{ $it['label'] }}@if($it['note'])<small>{{ $it['note'] }}</small>@endif</th><td>{{ $rp($it['amount']) }}</td></tr>
          @endforeach
        </tbody>
        <tfoot><tr><th scope="row">Total</th><td>{{ $rp($total) }}</td></tr></tfoot>
      </table>
    </section>
    @endforeach
  </div>

  @if($history->count() > 1)
  @php $max = max(1, $history->max(fn ($r) => max($r->incomeTotal(), $r->expenseTotal()))); @endphp
  <section class="card" aria-labelledby="h-tren">
    <h2 id="h-tren">Pemasukan dan pengeluaran per bulan</h2>
    <div class="lk-chart" role="img" aria-label="Grafik batang pemasukan dan pengeluaran {{ $history->count() }} bulan terakhir">
      @foreach($history as $h)
      <a href="{{ route('laporan.show', $h->slug()) }}" class="lk-col{{ $h->is($report) ? ' on' : '' }}" title="{{ $h->label() }}: masuk {{ $rp($h->incomeTotal()) }}, keluar {{ $rp($h->expenseTotal()) }}">
        <span class="bars"><i class="in" style="--v:{{ round($h->incomeTotal() / $max * 100) }}%"></i><i class="out" style="--v:{{ round($h->expenseTotal() / $max * 100) }}%"></i></span>
        <small>{{ $h->period->translatedFormat('M y') }}</small>
      </a>
      @endforeach
    </div>
    <p class="src-note"><span class="lk-key in"></span> Pemasukan &nbsp; <span class="lk-key out"></span> Pengeluaran</p>
  </section>
  @endif

  <div class="lk-foot">
    @if($report->source_note)<p class="src-note">Sumber: {{ $report->source_note }}@if($report->approved_at) Disetujui {{ $report->approved_at->timezone('Asia/Makassar')->translatedFormat('j F Y') }}.@endif</p>@endif
    @if($report->poster)<a class="btn ghost small" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($report->poster) }}" target="_blank" rel="noopener">Lihat poster resmi</a>@endif
    <a class="btn small" href="{{ route('infak') }}">Rekening &amp; QRIS infak</a>
  </div>

  @if($archive->count() > 1)
  <nav class="card" aria-labelledby="h-arsip">
    <h2 id="h-arsip">Arsip laporan</h2>
    <ul class="lk-archive">
      @foreach($archive as $a)
      <li><a href="{{ route('laporan.show', $a->slug()) }}"@if($a->is($report)) aria-current="page"@endif>{{ $a->label() }}</a><span>{{ $rp($a->closingBalance()) }}</span></li>
      @endforeach
    </ul>
  </nav>
  @endif
  @endif
</section>
@endsection
