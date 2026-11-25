<!doctype html>
@php $site ??= \App\Models\Setting::site(); $wa = fn ($n) => \App\Support\SafeHtml::wa($n); @endphp
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  {{-- title_full: judul utuh untuk beranda; isi @section sebaris sudah di-escape oleh Blade --}}
  <title>{!! trim($__env->yieldContent('title_full')) ?: e(isset($title) ? $title.' | '.(mb_strlen($title) > 32 ? "Masjid Imam Syafi'i" : "Masjid Imam Syafi'i Banjarmasin") : "Masjid Imam Syafi'i Banjarmasin") !!}</title>
  <meta name="description" content="{{ $description ?? "Masjid Imam Syafi'i Banjarmasin: jadwal shalat, jadwal kajian Islam ilmiah, berita, dan informasi jamaah." }}">
  <meta property="og:title" content="{{ $title ?? "Masjid Imam Syafi'i Banjarmasin" }}">
  <link rel="canonical" href="{{ url()->current() }}">
  <meta property="og:site_name" content="Masjid Imam Syafi'i Banjarmasin">
  <meta property="og:locale" content="id_ID">
  <meta property="og:type" content="{{ $ogType ?? 'website' }}">
  <meta property="og:url" content="{{ url()->current() }}">
  <meta property="og:description" content="{{ $description ?? "Jadwal shalat, jadwal kajian Islam ilmiah, berita, dan layanan jamaah Masjid Imam Syafi'i Banjarmasin." }}">
  <meta name="twitter:card" content="summary_large_image">
@stack('meta')
  <meta property="og:image" content="{{ $ogImage ?? asset('images/facade.jpg') }}">
  <link rel="icon" href="{{ asset('images/logo-red.png') }}">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
  <meta name="theme-color" content="#2A0B0D">
  <link rel="preload" href="/fonts/plus-jakarta-sans-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/fonts/newsreader-latin-600-normal.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
@stack('jsonld')
</head>
<body>
<a class="skip" href="#konten">Lompat ke konten</a>
<header class="topbar"><div class="wrap topbar-in">
  <a class="brand" href="{{ route('home') }}" aria-label="Beranda Masjid Imam Syafi'i Banjarmasin">
    <span class="logo" role="img" aria-label="Logo Masjid Imam Syafi'i"></span>
    <span class="brand-t"><span class="ar" lang="ar">مسجد الإمام الشافعي</span><strong>Masjid Imam Syafi'i</strong></span>
  </a>
  <nav class="main" aria-label="Menu utama"><ul id="nav">
    <li><a href="{{ route('kajian') }}" @if(request()->routeIs('kajian*')) aria-current="page" @endif>Kajian</a></li>
    <li><a href="{{ route('program') }}" @if(request()->routeIs('program*')) aria-current="page" @endif>Program</a></li>
    <li><a href="{{ route('berita') }}" @if(request()->routeIs('berita*')) aria-current="page" @endif>Berita</a></li>
    @php $tentang = request()->routeIs('profil', 'halaman', 'layanan', 'kontak', 'shalat', 'laporan*'); @endphp
    <li class="has-sub"><details class="navsub">
      <summary @if($tentang) aria-current="page" @endif>Tentang masjid @include('site.partials.icon',['name'=>'chev','size'=>14])</summary>
      <ul>
        <li><a href="{{ route('profil') }}" @if(request()->routeIs('profil', 'halaman')) aria-current="page" @endif>Profil masjid</a></li>
        <li><a href="{{ route('layanan') }}" @if(request()->routeIs('layanan')) aria-current="page" @endif>Layanan jamaah</a></li>
        <li><a href="{{ route('shalat') }}" @if(request()->routeIs('shalat')) aria-current="page" @endif>Jadwal shalat sebulan</a></li>
        <li><a href="{{ route('laporan') }}" @if(request()->routeIs('laporan*')) aria-current="page" @endif>Laporan keuangan</a></li>
        <li><a href="{{ route('kontak') }}" @if(request()->routeIs('kontak')) aria-current="page" @endif>Kontak &amp; lokasi</a></li>
      </ul>
    </details></li>
  </ul></nav>
  <a class="btn gold hdr-cta" href="{{ route('infak') }}" @if(request()->routeIs('infak')) aria-current="page" @endif>@include('site.partials.icon',['name'=>'gift','size'=>18]) Infak</a>
  <div class="tools">
    <a class="ibtn" href="{{ route('cari') }}" title="Cari" aria-label="Cari di situs" @if(request()->routeIs('cari')) aria-current="page" @endif>@include('site.partials.icon',['name'=>'search'])</a>
    <button class="ibtn" id="btn-size" type="button" aria-pressed="false" title="Perbesar huruf" aria-label="Perbesar huruf">@include('site.partials.icon',['name'=>'text'])</button>
    <button class="ibtn" id="btn-theme" type="button" title="Ganti tema" aria-label="Ganti tema terang atau gelap">@include('site.partials.icon',['name'=>'moon'])</button>
  </div>
</div></header>
@if(\App\Models\Setting::noticeVisible($site))
<div class="pita {{ $site['notice_tone'] }}" role="status"><div class="wrap pita-in">@include('site.partials.icon',['name'=>'cal','size'=>18])<span>{{ $site['notice_text'] }}</span>@if($site['notice_url'])<a href="{{ $site['notice_url'] }}">Selengkapnya</a>@endif</div></div>
@endif
<nav class="bnav" aria-label="Menu cepat"><ul id="bnav" style="grid-template-columns:repeat(5,1fr)">
  <li><a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>@include('site.partials.icon',['name'=>'home','size'=>22])<span>Beranda</span></a></li>
  <li><a href="{{ route('kajian') }}" @if(request()->routeIs('kajian*')) aria-current="page" @endif>@include('site.partials.icon',['name'=>'book','size'=>22])<span>Kajian</span></a></li>
  <li><a href="{{ route('infak') }}" @if(request()->routeIs('infak')) aria-current="page" @endif>@include('site.partials.icon',['name'=>'gift','size'=>22])<span>Infak</span></a></li>
  <li><a href="{{ route('berita') }}" @if(request()->routeIs('berita*')) aria-current="page" @endif>@include('site.partials.icon',['name'=>'news','size'=>22])<span>Berita</span></a></li>
  <li><button type="button" id="btn-menu" aria-haspopup="dialog" aria-controls="sheet" @if(request()->routeIs('program*', 'layanan', 'profil', 'halaman', 'kontak', 'shalat')) aria-current="page" @endif>@include('site.partials.icon',['name'=>'menu','size'=>22])<span>Lainnya</span></button></li>
</ul></nav>
<dialog class="sheet" id="sheet" aria-labelledby="sheet-h">
  <div class="sheet-top"><p class="sheet-title" id="sheet-h">Menu</p><button class="ibtn" type="button" data-close aria-label="Tutup menu">@include('site.partials.icon',['name'=>'close','size'=>20])</button></div>
  <nav aria-label="Semua halaman"><ul class="sheet-links">
    @foreach([['home','home','Beranda'],['kajian','book','Jadwal kajian'],['shalat','cal','Jadwal shalat'],['berita','news','Berita'],['infak','gift','Infak'],['program','spark','Program masjid'],['layanan','heart','Layanan'],['profil','home','Profil masjid'],['kontak','phone','Kontak & lokasi'],['cari','search','Cari di situs']] as [$r,$ic,$l])
    <li><a href="{{ route($r) }}" @if(request()->routeIs($r.'*')) aria-current="page" @endif>@include('site.partials.icon',['name'=>$ic,'size'=>20])<span>{{ $l }}</span></a></li>
    @endforeach
  </ul></nav>
  <div class="sheet-cta"><a class="btn gold" href="{{ $site['live_url'] }}" target="_blank" rel="noopener">@include('site.partials.icon',['name'=>'play','size'=>18]) Siaran langsung</a><a class="btn ghost" href="{{ $wa($site['fab_number']) }}" target="_blank" rel="noopener">@include('site.partials.icon',['name'=>'wa','size'=>18]) {{ $site['fab_label'] }}</a></div>
</dialog>
<a class="fab" href="{{ $wa($site['fab_number']) }}" target="_blank" rel="noopener" aria-label="{{ $site['fab_label'] }} lewat WhatsApp">@include('site.partials.icon',['name'=>'wa','size'=>22])<span>{{ $site['fab_label'] }}</span></a>

<main id="konten" class="wrap" tabindex="-1">
@yield('content')
</main>

<div class="kain" aria-hidden="true"><div class="gigi"></div><div class="ombak"></div><div class="hiris"></div><div class="ombak"></div><div class="gigi flip"></div></div>
<footer class="site"><div class="wrap foot4">
  <div class="fcol">
    <div class="foot-brand"><span class="logo" role="img" aria-label="Logo Masjid Imam Syafi'i"></span><div><span class="callig" lang="ar">مسجد الإمام الشافعي</span><b>Masjid Imam Syafi'i Banjarmasin</b></div></div>
    <p>{{ $site['footer_about'] }}</p>
    <ul class="flinks"><li><a href="{{ route('profil') }}">Profil masjid</a></li><li><a href="{{ route('program') }}">Program masjid</a></li><li><a href="{{ route('layanan') }}">Layanan jamaah</a></li><li><a href="{{ route('laporan') }}">Laporan keuangan</a></li><li><a href="{{ route('infak') }}">Rekening &amp; QRIS infak</a></li><li><a href="{{ route('shalat') }}">Jadwal shalat sebulan</a></li><li><a href="{{ route('kontak') }}">Semua kontak</a></li></ul>
  </div>
  <div class="fcol"><h2>Hubungi kami</h2>
    <ul class="fcontact">
      @foreach(collect($site['contacts'])->where('footer', true) as $c)
      <li>@include('site.partials.icon',['name'=>'wa','size'=>18])<a href="{{ $wa($c['number']) }}" target="_blank" rel="noopener">{{ $c['number'] }} ({{ $c['label'] }})</a></li>
      @endforeach
      <li>@include('site.partials.icon',['name'=>'pin','size'=>18])<span>{{ $site['address'] }}{{ $site['postal_code'] ? ' '.$site['postal_code'] : '' }}</span></li>
    </ul></div>
  <div class="fcol"><h2>Media sosial</h2>
    @php $byPlatform = collect($site['socials'])->groupBy('platform'); @endphp
    <div class="socials">@foreach(['facebook' => 'fb', 'youtube' => 'yt', 'instagram' => 'ig', 'tiktok' => 'tiktok'] as $pf => $ic)@if($first = $byPlatform->get($pf)?->first())<a href="{{ $first['url'] }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($pf) }}">@include('site.partials.icon',['name'=>$ic,'size'=>22])</a>@endif @endforeach</div>
    <ul class="flinks">@foreach(collect($site['socials'])->filter(fn ($s) => $s['platform'] === 'link' || ($s['platform'] === 'instagram' && $s !== $byPlatform->get('instagram')->first())) as $s)<li><a href="{{ $s['url'] }}" target="_blank" rel="noopener">{{ $s['handle'] }}</a>{{ $s['note'] ? ', '.mb_strtolower($s['note']) : '' }}</li>@endforeach</ul></div>
  <div class="fcol"><h2>Lokasi</h2>
    <a class="mapcard" href="https://www.google.com/maps/search/?api=1&amp;query={{ $site['latitude'] }}%2C{{ $site['longitude'] }}" target="_blank" rel="noopener"><span class="pin">@include('site.partials.icon',['name'=>'pin','size'=>18])</span><span class="mc-t"><b>Masjid Imam Syafi'i</b><span>Pemurus Dalam, Banjarmasin Selatan</span></span><span class="mc-go">Buka peta &amp; rute</span></a></div>
</div>
<div class="wrap fbottom"><p>Hak cipta dilindungi undang-undang, {{ now()->year }}, Tim Sekretariat Masjid Imam Syafi'i Banjarmasin</p></div>
</footer>
<script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}"></script>
</body>
</html>
