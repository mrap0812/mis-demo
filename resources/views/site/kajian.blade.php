@extends('layouts.site', ['title' => 'Jadwal kajian'])
@section('content')
<section class="view">
  <div class="phead"><div class="wrap phead-in"><nav class="crumb2" aria-label="Breadcrumb"><a href="{{ route('home') }}">Beranda</a><span>/</span><span>Kajian</span></nav>
    <p class="eyebrow-k light"><span lang="ar">مجالس العلم</span> Majelis ilmu</p><h1>Jadwal kajian</h1>
    <p class="plede">Gratis dan terbuka untuk umum, Muslim dan Muslimah. Disiarkan langsung dan diarsipkan per kitab di YouTube.</p>
    <div class="row phead-cta"><a class="btn gold" href="{{ $site['wa_group_url'] }}" target="_blank" rel="noopener">Gabung grup WA info kajian</a><a class="btn outline-light" href="#langganan">Langganan kalender</a><a class="btn outline-light" href="{{ \App\Support\SafeHtml::wa($site['fab_number']) }}" target="_blank" rel="noopener">@include('site.partials.icon',['name'=>'wa','size'=>18]) {{ $site['fab_label'] }}</a></div></div></div>
  <form class="toolbar" method="get" action="{{ route('kajian') }}"><div class="filters">
    <label class="search" for="q"><span class="sr">Cari kajian</span>@include('site.partials.icon',['name'=>'search','size'=>18])<input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Cari judul, kitab, atau nama ustadz"></label>
    <div class="seg" role="group" aria-label="Jenis">
      @foreach(['' => 'Semua', 'kajian' => 'Kajian', 'berkala' => 'Berkala', 'khutbah' => 'Khutbah', 'muslimah' => 'Muslimah'] as $k => $l)
        <button type="submit" name="jenis" value="{{ $k }}" aria-pressed="{{ request('jenis', '') === $k ? 'true' : 'false' }}">{{ $l }}</button>
      @endforeach
    </div>
    <label class="check" for="selesai"><input id="selesai" name="selesai" type="checkbox" value="1" @checked($past) data-autosubmit> Tampilkan yang sudah selesai</label>
  </div></form>
  @php $today = \Carbon\CarbonImmutable::now(\App\Services\KajianSchedule::TZ)->startOfDay(); @endphp
  @php
    $days = $items->groupBy(fn ($o) => $o['date']->toDateString());
    // Tanpa filter: tampilkan 7 hari pertama, sisanya dilipat agar halaman tidak terlalu panjang di ponsel.
    $filtered = request()->filled('q') || request()->filled('jenis') || $past;
    $cut = $today->addDays(6)->toDateString();
    $first = $filtered ? $days : $days->filter(fn ($l, $date) => $date <= $cut);
    $rest = $filtered ? collect() : $days->filter(fn ($l, $date) => $date > $cut);
  @endphp
  <div class="agenda">
    @forelse($first as $date => $list)
      @include('site.partials.agenda-day', ['list' => $list, 'today' => $today])
    @empty
      <div class="callout">Tidak ada kajian yang cocok. Coba hapus kata kunci atau pilih Semua.</div>
    @endforelse
    @if($rest->isNotEmpty())
      <details class="agenda-more">
        <summary><span class="am-open">Tampilkan 7 hari berikutnya</span><span class="am-close">Sembunyikan 7 hari berikutnya</span> <small>{{ $rest->flatten(1)->count() }} kajian</small></summary>
        <div class="agenda">@foreach($rest as $date => $list) @include('site.partials.agenda-day', ['list' => $list, 'today' => $today]) @endforeach</div>
      </details>
    @endif
  </div>

  @php $icsUrl = route('kajian.ics'); $webcal = preg_replace('#^https?://#', 'webcal://', $icsUrl); @endphp
  <section class="subcal" id="langganan" aria-labelledby="sub-h">
    <div><h2 id="sub-h">Langganan jadwal kajian di kalender ponsel</h2>
      <p>Jadwal 60 hari ke depan masuk ke kalender Anda dan ikut diperbarui otomatis, termasuk bila ada kajian yang dibatalkan atau ditunda.</p></div>
    <div class="row">
      <a class="btn small" href="https://calendar.google.com/calendar/r?cid={{ urlencode($webcal) }}" target="_blank" rel="noopener">@include('site.partials.icon',['name'=>'cal','size'=>16]) Google Calendar</a>
      <a class="btn small ghost" href="{{ $webcal }}">iPhone / Outlook</a>
      <button class="copy" type="button" data-copy="{{ $icsUrl }}">Salin tautan</button>
    </div>
  </section>


  @if(filled($site['hadith_id']))
  <aside class="hadith hadith-k" aria-label="Keutamaan menuntut ilmu">
    <p class="eyebrow-k"><span lang="ar">فضل العلم</span> Keutamaan ilmu</p>
    @if(filled($site['hadith_ar']))<p class="h-ar" lang="ar">{{ $site['hadith_ar'] }}</p>@endif
    <p class="h-id">{{ $site['hadith_id'] }}</p>
    @if(filled($site['hadith_ref']))<p class="h-ref">{{ $site['hadith_ref'] }}</p>@endif
  </aside>
  @endif

  <section class="sec" aria-labelledby="kt-h">
    <div class="sec-h"><div><p class="eyebrow-k"><span lang="ar">الكتب</span> Kitab</p><h2 id="kt-h">Kitab yang sedang dikaji</h2></div></div>
    <div class="kitabs" tabindex="0" role="region" aria-label="Daftar kitab, geser ke samping untuk melihat lainnya">
      @foreach($series as $s)
        <div class="kitab" style="--c:{{ $s->color }}"><div class="k-band"><span lang="ar">{{ $s->kitab_ar }}</span></div><div class="k-body">
          <p class="k-when">{{ $s->badge ? $s->badge.' · ' : 'Setiap ' }}{{ \App\Models\KajianSeries::DAYS[$s->day_of_week] }} · {{ $s->slotLabel() }}</p>
          <h3>{{ $s->title }}</h3><p class="k-who">{{ $s->speaker?->name }}</p>@if($s->kitab)<p class="k-who">Kitab: {{ $s->kitab }}</p>@endif
        </div></div>
      @endforeach
    </div>
  </section>
  <section class="sec poster-sec" aria-labelledby="ps-h">
    <div class="ps-txt"><p class="eyebrow-k"><span lang="ar">الإعلان</span> Poster resmi</p><h2 id="ps-h">Jadwal kajian dalam satu poster</h2>
      <p>Poster jadwal sepekan dari tim media masjid. Isi jadwal di halaman ini dibuat dari poster yang sama agar bisa dicari dan dibaca pembaca layar.</p>
      <button class="btn" type="button" id="poster-btn" aria-expanded="false">Perbesar poster</button></div>
    <figure class="ps-fig" id="poster-fig"><img src="{{ $site['kajian_poster'] ? \Illuminate\Support\Facades\Storage::disk('public')->url($site['kajian_poster']) : asset('images/poster-jadwal-shafar-1448.jpg') }}"{!! \App\Support\Img::attrs($site['kajian_poster'], '(max-width: 899px) 92vw, 560px') !!} alt="{{ $site['kajian_poster_alt'] }}" width="1080" height="1350" loading="lazy"></figure>
  </section>
</section>
@endsection
