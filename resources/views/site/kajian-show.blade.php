@extends('layouts.site', ['title' => $o['title'], 'description' => trim($o['title'].($o['speaker'] ? ' bersama '.$o['speaker'] : '').', '.$o['date']->translatedFormat('l, j F Y').'. '.$o['time_label'])])
@php
  $st = \App\Services\KajianSchedule::status($o); $s = $o['series']; $d = $o['date'];
  $stLabel = ['terjadwal' => 'Terjadwal', 'berlangsung' => 'Sedang berlangsung', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan', 'ditunda' => 'Ditunda'][$st] ?? ucfirst($st);
  $stc = ['terjadwal' => 'info', 'berlangsung' => 'ok', 'dibatalkan' => 'bad', 'ditunda' => 'warn'][$st] ?? '';
  $cat = \App\Models\KajianSeries::CATEGORIES[$s->category] ?? null;
  $waLink = fn ($n) => \App\Support\SafeHtml::wa($n);
@endphp
@section('content')
<section class="view">
  <div class="phead"><div class="wrap phead-in kshow">
    <div class="kshow-txt">
      <nav class="crumb2" aria-label="Breadcrumb"><a href="{{ route('home') }}">Beranda</a><span>/</span><a href="{{ route('kajian') }}">Kajian</a><span>/</span><span>{{ \Illuminate\Support\Str::limit($o['title'], 40) }}</span></nav>
      <p class="eyebrow-k light">{{ $s->slotLabel() }}{{ $cat ? ' · '.$cat : '' }}</p>
      <h1>{{ $o['title'] }}</h1>
      <p class="plede">{{ $d->translatedFormat('l, j F Y') }} · {{ $o['speaker'] ?? 'Khatib diumumkan di '.$site['info_handle'] }}</p>
      <div class="row"><span class="chip {{ $stc }}">{{ $stLabel }}</span>@if($o['source'] === 'poster')<span class="chip ok">Tema pertemuan dari poster</span>@endif @if($s->badge)<span class="chip sim">{{ $s->badge }}</span>@endif</div>
    </div>
    @if($s->kitab_ar)<div class="kshow-ar" style="--c:{{ $s->color }}" aria-hidden="true"><span lang="ar">{{ $s->kitab_ar }}</span></div>@endif
  </div></div>

  <div class="kshow-grid">
    <div class="sect">
      @if($st === 'dibatalkan')<div class="callout bad" role="alert"><strong>Pertemuan ini dibatalkan.</strong> {{ $o['status_reason'] }}</div>@endif
      @if($st === 'ditunda')<div class="callout warn" role="alert"><strong>Pertemuan ini ditunda.</strong> {{ $o['status_reason'] }}</div>@endif
      @if($st === 'selesai')<div class="callout">Kajian ini sudah selesai. @if($o['recording_url'])<a href="{{ $o['recording_url'] }}" target="_blank" rel="noopener">Tonton rekamannya</a>.@else Rekamannya tersedia di kanal YouTube masjid.@endif</div>@endif
      <dl class="facts">
        <div class="fact"><dt>Tanggal</dt><dd>{{ $d->translatedFormat('l, j F Y') }} <span class="note">({{ $hijri }})</span></dd></div>
        <div class="fact"><dt>Waktu</dt><dd>{{ $o['time_label'] }}</dd></div>
        <div class="fact"><dt>Tempat</dt><dd>Masjid Imam Syafi'i Banjarmasin · <a href="{{ route('kontak') }}">lokasi &amp; rute</a></dd></div>
        @if($s->kitab)<div class="fact"><dt>Kitab / seri</dt><dd>{{ $s->kitab }}</dd></div>@endif
        <div class="fact"><dt>{{ $s->category === 'khutbah' ? 'Khatib' : 'Pemateri' }}</dt><dd>{{ $o['speaker'] ?? 'Diumumkan di '.$site['info_handle'] }}</dd></div>
        @if($o['notes'])<div class="fact"><dt>Keterangan</dt><dd>{{ $o['notes'] }}</dd></div>@endif
        <div class="fact"><dt>Peserta</dt><dd>{{ $s->category === 'muslimah' ? 'Khusus Muslimah' : 'Gratis dan terbuka untuk umum (Muslim & Muslimah)' }}</dd></div>
      </dl>
      <p class="src-note">Jam mulai adalah perkiraan dari jadwal shalat hari itu. Perubahan diumumkan di <a href="{{ $site['info_url'] }}" target="_blank" rel="noopener">{{ $site['info_handle'] }}</a>.</p>
    </div>

    <aside class="prof-side">
      <div class="card"><h2>Ikuti kajian ini</h2>
        <div class="stack">
          @if(! in_array($st, ['dibatalkan', 'selesai']))
            @php
              $gc = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text='.rawurlencode($o['title']).'&dates='.$o['starts_at']->utc()->format('Ymd\THis\Z').'/'.$o['ends_at']->utc()->format('Ymd\THis\Z').'&location='.rawurlencode("Masjid Imam Syafi'i Banjarmasin").'&ctz=Asia/Makassar';
              $share = 'https://wa.me/?text='.rawurlencode($o['title'].($o['speaker'] ? ' bersama '.$o['speaker'] : '').', '.$d->translatedFormat('l, j F Y').', '.$o['time_label'].". Masjid Imam Syafi'i Banjarmasin. ".url()->current());
            @endphp
            <a class="btn" href="{{ $gc }}" target="_blank" rel="noopener">@include('site.partials.icon',['name'=>'cal','size'=>18]) Tambah ke Google Calendar</a>
            <a class="btn ghost" href="{{ $share }}" target="_blank" rel="noopener">@include('site.partials.icon',['name'=>'share','size'=>18]) Bagikan lewat WhatsApp</a>
          @endif
          <a class="btn ghost" href="{{ $site['live_url'] }}" target="_blank" rel="noopener">@include('site.partials.icon',['name'=>'play','size'=>18]) Siaran langsung</a>
          <a class="btn ghost" href="{{ $s->playlist_url ?: $site['playlist_url'] }}" target="_blank" rel="noopener">@include('site.partials.icon',['name'=>'tv','size'=>18]) Rekaman seri ini</a>
        </div>
      </div>
      @if($next->isNotEmpty())
      <div class="card"><h2>Pertemuan berikutnya</h2>
        <ul class="nextlist">@foreach($next as $n)
          <li><a href="{{ route('kajian.show', [$s->code, $n['date']->format('Ymd')]) }}"><b>{{ $n['date']->translatedFormat('D, j M') }}</b><span>{{ $n['title'] }}</span>@if($n['status_override'])<em class="chip {{ $n['status_override'] === 'dibatalkan' ? 'bad' : 'warn' }}">{{ ucfirst($n['status_override']) }}</em>@endif</a></li>
        @endforeach</ul>
      </div>
      @endif
      <p class="note">Ada pertanyaan? <a href="{{ $waLink($site['fab_number']) }}" target="_blank" rel="noopener">{{ $site['fab_label'] }} ({{ $site['fab_number'] }})</a></p>
    </aside>
  </div>
</section>
@endsection

@push('jsonld')
<script type="application/ld+json">{!! json_encode(array_filter([
  '@context' => 'https://schema.org', '@type' => 'Event',
  'name' => $o['title'], 'startDate' => $o['starts_at']->toIso8601String(), 'endDate' => $o['ends_at']->toIso8601String(),
  'eventStatus' => 'https://schema.org/'.(['dibatalkan' => 'EventCancelled', 'ditunda' => 'EventPostponed'][$o['status_override']] ?? 'EventScheduled'),
  'eventAttendanceMode' => 'https://schema.org/MixedEventAttendanceMode',
  'isAccessibleForFree' => true,
  'location' => ['@type' => 'Place', 'name' => "Masjid Imam Syafi'i Banjarmasin", 'address' => $site['address']],
  'performer' => $o['speaker'] ? ['@type' => 'Person', 'name' => $o['speaker']] : null,
  'organizer' => ['@type' => 'Organization', 'name' => "Masjid Imam Syafi'i Banjarmasin", 'url' => url('/')],
  'description' => trim(($o['series']->kitab ? 'Kitab: '.$o['series']->kitab.'. ' : '').($o['notes'] ?? '')) ?: null,
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush
