@extends('layouts.site', ['title' => 'Infak', 'description' => "Rekening dan QRIS resmi Masjid Imam Syafi'i Banjarmasin. Salurkan infak hanya ke data yang tercantum di halaman ini."])
@php $pusat = $site['contacts'][0] ?? null; $wa = fn ($n) => \App\Support\SafeHtml::wa($n); @endphp
@section('content')
<section class="view">
  @include('site.partials.phead', ['crumb' => 'Infak', 'ar' => 'الإنفاق', 'eyebrow' => 'Infak & sedekah', 'h1' => 'Infak untuk masjid', 'lede' => 'Salurkan infak hanya ke rekening atau QRIS yang tercantum di halaman ini. Setiap data di sini diperiksa dan disetujui oleh dua pengurus.'])

  <div class="callout warn infak-warn" role="note">
    <strong>Waspada penipuan.</strong> Pengurus tidak pernah meminta transfer lewat pesan pribadi, telepon, atau nomor rekening yang tidak tercantum di sini.
    @if($pusat) Bila ragu, tanyakan ke <a href="{{ $wa($pusat['number']) }}" target="_blank" rel="noopener">{{ $pusat['label'] }} ({{ $pusat['number'] }})</a>.@endif
  </div>

  @if($campaigns->isNotEmpty())
  <section class="stack" aria-labelledby="camp-h"><h2 id="camp-h">Kampanye donasi</h2>@include('site.partials.campaigns')</section>
  @endif

  <div class="infak-grid">
  <div>
  @if($accounts->isEmpty())
    <div class="card empty">
      <h2>Data rekening sedang diverifikasi</h2>
      <p>Rekening dan QRIS resmi akan tampil di sini setelah diperiksa pengurus. Sementara itu, silakan tanyakan cara berinfak ke sekretariat.</p>
      @if($pusat)<div class="row"><a class="btn small" href="{{ $wa($pusat['number']) }}" target="_blank" rel="noopener">@include('site.partials.icon',['name'=>'wa','size'=>16]) {{ $pusat['label'] }}</a></div>@endif
    </div>
  @else
    <div class="acc-grid">
      @foreach($accounts as $a)
      <article class="acc{{ $a->kind === 'qris' ? ' is-qris' : '' }}">
        <p class="acc-purpose">{{ $a->purpose }}</p>
        @if($a->kind === 'rekening')
          <p class="acc-bank">{{ $a->bank_name }}</p>
          <p class="acc-num">{{ $a->account_number }}</p>
          <p class="acc-holder">a.n. {{ $a->account_holder }}</p>
          <div class="row"><button class="btn small" type="button" data-copy="{{ \App\Support\SafeHtml::digits($a->account_number) }}">Salin nomor rekening</button></div>
        @else
          @if($a->qrisUrl())<img class="acc-qris" src="{{ $a->qrisUrl() }}" alt="Kode QRIS {{ $a->purpose }} atas nama {{ $a->qris_merchant }}" width="260" height="260" loading="lazy">@endif
          <p class="acc-holder">Merchant: <b>{{ $a->qris_merchant }}</b></p>
          <p class="note">Pindai dengan aplikasi bank atau dompet digital. Pastikan nama merchant di atas yang muncul sebelum membayar.</p>
        @endif
        @if($a->notes)<p class="note">{{ $a->notes }}</p>@endif
        <p class="acc-verified">@include('site.partials.icon',['name'=>'cal','size'=>14]) Diverifikasi {{ $a->approved_at?->timezone('Asia/Makassar')->translatedFormat('j F Y') }}</p>
      </article>
      @endforeach
    </div>
  @endif

  </div>
  <aside class="card infak-how" aria-labelledby="how-h">
    <h2 id="how-h">Cara berinfak</h2>
    <ol class="infak-steps">
      <li><span><b>Salin nomor dari halaman ini</b>, bukan dari pesan berantai atau tangkapan layar kiriman orang.</span></li>
      <li><span><b>Transfer lewat aplikasi bank atau ATM.</b> Sebelum mengirim, pastikan nama penerima sama dengan nama "a.n." di kartu.</span></li>
      <li><span><b>Simpan bukti transfer.</b> Konfirmasi tidak wajib; bila ingin, kirimkan ke sekretariat.</span></li>
    </ol>
  </aside>
  </div>

  <p class="infak-report">@include('site.partials.icon',['name'=>'arrow','size'=>16]) <a href="{{ route('laporan') }}">Lihat laporan penggunaan infak setiap bulan</a></p>
  <p class="src-note">Situs ini tidak memproses pembayaran dan tidak menyimpan data transfer Anda. Untuk konfirmasi atau pertanyaan, hubungi <a href="{{ route('kontak') }}">sekretariat</a>.</p>
</section>
@endsection
