@extends('layouts.site', ['title' => 'Profil', 'description' => $page->lede])
@section('content')
<section class="view">
  @include('site.partials.phead', ['crumb' => 'Profil', 'ar' => 'عن المسجد', 'eyebrow' => 'Tentang masjid', 'h1' => $page->title, 'lede' => $page->lede])
  <div class="profile-hero"><img src="{{ $page->cover ? \Illuminate\Support\Facades\Storage::disk('public')->url($page->cover) : asset('images/facade.jpg') }}"{!! \App\Support\Img::attrs($page->cover, '100vw') !!} alt="Fasad Masjid Imam Syafi'i Banjarmasin" width="1600" height="540" decoding="async"></div>
  <div class="prof-grid">
    <div class="prose">{!! $page->safeBody() !!}</div>
    <aside class="prof-side">
      @include('site.partials.location')
      @if($history->isNotEmpty())
      <div class="card"><h2>Jejak kegiatan</h2>
        <ol class="timeline">@foreach($history as $p)<li><b>{{ $p->published_at->year }}</b><a href="{{ route('berita.show', $p->slug) }}">{{ $p->title }}</a></li>@endforeach</ol>
        <a class="link-arrow" href="{{ route('berita') }}">Semua berita</a></div>
      @endif
    </aside>
  </div>
  @if($speakers->isNotEmpty())
  <section class="sec" aria-labelledby="ust-h">
    <div class="sec-h"><div><p class="eyebrow-k"><span lang="ar">الأساتذة</span> Asatidzah</p><h2 id="ust-h">Pemateri kajian</h2></div><a class="link-arrow" href="{{ route('kajian') }}">Jadwal kajian</a></div>
    <ul class="ustadz">
      @foreach($speakers as $s)
        @php $n = preg_replace(['/^Ustadz (Dr\. )?/', '/,.*$/'], '', $s->name); $ini = collect(explode(' ', $n))->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''); @endphp
        <li><span class="ini" aria-hidden="true">{{ $ini }}</span><span><b>{{ $s->name }}</b>@if($s->series_count)<br><small>{{ $s->series_count }} majelis rutin</small>@endif</span></li>
      @endforeach
    </ul>
  </section>
  @endif
  @if($pages->isNotEmpty())
  <section class="sec"><div class="sec-h"><div><p class="eyebrow-k"><span lang="ar">المزيد</span> Selengkapnya</p><h2>Tentang masjid</h2></div></div>
    <div class="services">@foreach($pages as $p)<a class="svc" href="{{ $p->url() }}"><h3>{{ $p->title }}</h3>@if($p->lede)<p>{{ $p->lede }}</p>@endif<span class="link-arrow">Baca</span></a>@endforeach</div>
  </section>
  @endif
</section>
@endsection
