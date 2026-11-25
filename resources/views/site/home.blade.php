@extends('layouts.site')
@php
  use App\Services\PrayerTimes; use App\Models\KajianSeries;
  $names = [/* Imsak & Terbit ada di jadwal sebulan */ 'fajr'=>['Subuh',0],'dhuhr'=>['Dzuhur',0],'asr'=>['Ashar',0],'maghrib'=>['Maghrib',0],'isha'=>['Isya',0]];
@endphp
@section('title_full', "Masjid Imam Syafi'i Banjarmasin | Kajian Ilmiah Gratis & Jadwal Shalat")
@section('content')
<section class="view home" data-view="beranda">
  <div class="hero4">
    <img class="hero-bg" src="{{ asset('images/facade-1600.webp') }}" srcset="{{ asset('images/facade-800.webp') }} 800w, {{ asset('images/facade-1600.webp') }} 1600w" sizes="100vw" alt="" width="1600" height="540" fetchpriority="high" decoding="async">
    <div class="wrap hero4-in">
      <div class="h4-copy">
        <p class="kicker"><i></i>{{ $site['hero_kicker'] }}</p>
        <h1>{{ $site['hero_title'] }}</h1>
        <p class="sub">{{ $site['hero_sub'] }}</p>
        <div class="row cta"><a class="btn gold" href="{{ route('kajian') }}">Lihat jadwal kajian</a><a class="btn outline-light" href="{{ $site['live_url'] }}" target="_blank" rel="noopener">@include('site.partials.icon',['name'=>'play','size'=>18]) Siaran langsung</a></div>
        @if(! empty($site['hero_trust']))<p class="trust-line">{{ implode(' · ', $site['hero_trust']) }}</p>@endif
      </div>
      <aside class="hc" aria-labelledby="ph" id="prayer" data-times='@json($prayer)'>
        <div class="hc-head"><h2 id="ph">Jadwal shalat hari ini</h2><span class="tag">WITA</span></div>
        <p class="hc-date"><span>{{ $today->translatedFormat('l, j F Y') }}</span><span class="hijri">{{ $hijri }}</span></p>
        <div class="hc-next" aria-live="polite"><p id="p-next-label">Waktu berikutnya</p><p class="big" id="p-next">--</p></div>
        <div class="dayline" aria-hidden="true"><i id="p-line"></i></div>
        <div class="ptimes" id="p-times">
          @foreach($names as $k => [$label, $aux])
            <div class="pt{{ $aux ? ' aux' : '' }}" data-k="{{ $k }}"><span>{{ $label }}</span><b>{{ PrayerTimes::hhmm($prayer[$k]) }}</b></div>
          @endforeach
        </div>
        @if($nextKajian)
        <div class="hc-kajian"><a href="{{ route('kajian.show', [$nextKajian['series']->code, $nextKajian['date']->format('Ymd')]) }}"><span class="n-meta">Kajian berikutnya</span><b>{{ $nextKajian['title'] }}</b><span>{{ $nextKajian['date']->translatedFormat('l, j M') }} · {{ $nextKajian['time_label'] }}</span></a></div>
        @endif
        @if($programsToday->isNotEmpty())
        <div class="hc-today"><p class="n-meta">Hari ini juga di masjid</p><ul>@foreach($programsToday as $pt)<li><a href="{{ route('program.show', $pt->slug) }}"><b>{{ $pt->title }}</b>@if($pt->schedule)<span>{{ $pt->schedule }}</span>@endif</a></li>@endforeach</ul></div>
        @endif
        <p class="pfoot"><a href="{{ route('shalat') }}">Lihat jadwal sebulan</a></p>
      </aside>
    </div>
  </div>

  <section class="sec" aria-labelledby="wk-h">
    <div class="sec-h"><div><h2 id="wk-h">Kajian hari ini &amp; besok</h2></div><a class="link-arrow" href="{{ route('kajian') }}">Lihat sepekan @include('site.partials.icon',['name'=>'arrow','size'=>16])</a></div>
    <div class="tt" id="week">
      @foreach($days->take(2) as $day)
        @php $isToday = $day['date']->isSameDay($today); @endphp
        <div class="tt-day{{ $isToday ? ' today' : '' }}">
          <h3>{{ KajianSeries::DAYS[$day['dow']] }}<span>{{ $isToday ? 'Hari ini' : $day['date']->translatedFormat('j M') }}</span></h3>
          @foreach($day['items'] as $it)
            @php $s = $it['s']; @endphp
            @if($s->is_tentative)
              <div class="tt-c tent"><small>{{ $s->slotLabel() }}</small><b>Tentatif</b><span>Diumumkan di {{ $site['info_handle'] }}</span></div>
            @else
              <a class="tt-c{{ $s->weeks ? ' berkala' : '' }}{{ $it['active'] ? '' : ' off' }}" style="--c:{{ $s->color }}" href="{{ $it['active'] ? route('kajian.show', [$s->code, $day['date']->format('Ymd')]) : route('kajian') }}">
                <small>{{ $s->slotLabel() }}{{ $s->badge ? ' · '.$s->badge : '' }}</small><b>{{ $s->title }}</b><span>{{ $s->speaker?->displayShort() ?? 'Khatib bergilir' }}</span>
                @unless($it['active'])<em>Tidak ada pekan ini</em>@endunless
              </a>
            @endif
          @endforeach
        </div>
      @endforeach
    </div>
  </section>

  @if($campaigns->isNotEmpty())
  <section class="sec" aria-labelledby="camp-h">
    <div class="sec-h"><div><h2 id="camp-h">Kampanye donasi</h2></div><a class="link-arrow" href="{{ route('infak') }}">Cara berinfak @include('site.partials.icon',['name'=>'arrow','size'=>16])</a></div>
    @include('site.partials.campaigns')
  </section>
  @endif

  @if($programs->isNotEmpty())
  <section class="sec" aria-labelledby="prog-h">
    <div class="sec-h"><div><h2 id="prog-h">Ikut berbuat baik setiap pekan</h2></div><a class="link-arrow" href="{{ route('program') }}">Semua program @include('site.partials.icon',['name'=>'arrow','size'=>16])</a></div>
    <div class="progs">
      @foreach($programs as $p)
      <article class="prog-card">
        <a class="prog-thumb" href="{{ route('program.show', $p->slug) }}" tabindex="-1" aria-hidden="true">
          @if($p->poster)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($p->poster) }}"{!! \App\Support\Img::attrs($p->poster, '120px') !!} alt="" width="480" height="600" loading="lazy" decoding="async">
          @else<span class="prog-ph">@include('site.partials.icon',['name'=>$p->icon(),'size'=>40])</span>@endif
        </a>
        <div class="prog-body">
          <p class="prog-cat">@include('site.partials.icon',['name'=>$p->icon(),'size'=>16]) {{ $p->categoryLabel() }}@if($w = $p->whenLabel($today))<span class="prog-when">{{ $w }}</span>@endif</p>
          <h3 class="prog-h"><a href="{{ route('program.show', $p->slug) }}">{{ $p->title }}</a></h3>
          @if($p->schedule)<p class="prog-tag">{{ $p->schedule }}</p>@endif
        </div>
      </article>
      @endforeach
    </div>
  </section>
  @endif

  @if($posts->isNotEmpty())
  <section class="sec" aria-labelledby="news-h">
    <div class="sec-h"><div><h2 id="news-h">Berita terbaru</h2></div><a class="link-arrow" href="{{ route('berita') }}">Semua berita @include('site.partials.icon',['name'=>'arrow','size'=>16])</a></div>
    <div class="news-grid home-news compact-m">
      @foreach($posts as $i => $p)
        @include('site.partials.ncard', ['p' => $p, 'sizes' => $i === 0 ? '(max-width: 899px) 92vw, 380px' : '(max-width: 899px) 104px, 380px'])
      @endforeach
    </div>
  </section>
  @endif

  <section class="verse4" aria-label="Ayat pilihan">
    @if(filled($site['verse_ar']))<p class="v-ar" lang="ar">{{ $site['verse_ar'] }}</p>@endif
    @if(filled($site['verse_id']))<p class="v-id">{{ $site['verse_id'] }}</p>@endif
    @if(filled($site['verse_ref']))<p class="v-ref">{{ $site['verse_ref'] }}</p>@endif
  </section>
</section>
@endsection

@push('jsonld')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => [[
  '@type' => 'WebSite', 'name' => "Masjid Imam Syafi'i Banjarmasin", 'url' => url('/'), 'inLanguage' => 'id',
  'potentialAction' => ['@type' => 'SearchAction', 'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('cari').'?q={search_term_string}'], 'query-input' => 'required name=search_term_string'],
], array_filter([
  '@type' => 'Mosque',
  'name' => "Masjid Imam Syafi'i Banjarmasin", 'url' => url('/'), 'image' => asset('images/facade.jpg'),
  'telephone' => ($tel = \App\Support\SafeHtml::intl($site['contacts'][0]['number'] ?? null)) ? '+'.$tel : null,
  'address' => array_filter(['@type' => 'PostalAddress', 'streetAddress' => $site['address'], 'postalCode' => $site['postal_code'], 'addressLocality' => 'Banjarmasin', 'addressRegion' => 'Kalimantan Selatan', 'addressCountry' => 'ID']),
  'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $site['latitude'], 'longitude' => $site['longitude']],
  'sameAs' => collect($site['socials'])->where('platform', '!=', 'link')->pluck('url')->values()->all(),
])]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush
