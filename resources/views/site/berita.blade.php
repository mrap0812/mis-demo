@extends('layouts.site', ['title' => $q !== '' ? 'Cari "'.$q.'" di berita' : 'Berita & artikel', 'description' => "Kabar kegiatan, pengumuman, dan tulisan dari Masjid Imam Syafi'i Banjarmasin."])
@section('content')
<section class="view">
  @include('site.partials.phead', ['crumb' => 'Berita & artikel', 'ar' => 'الأخبار والمقالات', 'eyebrow' => 'Kabar', 'h1' => 'Berita & artikel', 'lede' => "Kabar kegiatan, pengumuman, dan tulisan dari Masjid Imam Syafi'i Banjarmasin."])
  <form class="toolbar" method="get" action="{{ route('berita') }}" role="search"><div class="filters">
    <label class="search" for="bq"><span class="sr-only">Cari berita</span>@include('site.partials.icon',['name'=>'search','size'=>18])<input id="bq" name="q" type="search" value="{{ $q }}" placeholder="Cari judul atau isi berita" maxlength="80"></label>
    <div class="seg" role="group" aria-label="Jenis tulisan">
      @foreach(['' => 'Semua'] + \App\Models\Post::TYPES as $k => $l)
        <a href="{{ route('berita', array_filter(['jenis' => $k, 'q' => $q])) }}" class="seg-a" @if($type === $k) aria-current="page" @endif>{{ $l }}</a>
      @endforeach
    </div>
    @if($type)<input type="hidden" name="jenis" value="{{ $type }}">@endif
  </div></form>

  @if($q !== '')
    <p class="result-note" role="status">{{ $posts->total() }} tulisan cocok dengan "<b>{{ $q }}</b>". <a href="{{ route('berita', array_filter(['jenis' => $type])) }}">Hapus pencarian</a></p>
  @endif

  @if($type === 'artikel' && $posts->isEmpty() && $q === '')
    <div class="empty-art"><span class="ar-eyebrow" lang="ar">المقالات</span><h3>Belum ada artikel yang terbit</h3><p>Rubrik ini disiapkan untuk tulisan para asatidzah. Setiap artikel melewati peninjauan pengelola dakwah sebelum terbit, lengkap dengan rujukan dalil.</p></div>
  @else
  <div class="news-grid compact-m">
    @forelse($posts as $p)
      @include('site.partials.ncard', ['p' => $p, 'sizes' => $loop->first ? '(max-width: 899px) 92vw, 380px' : '(max-width: 899px) 104px, 380px'])
    @empty
      <div class="callout">Tidak ada tulisan yang cocok. Coba kata kunci lain, atau <a href="{{ route('berita') }}">lihat semua berita</a>.</div>
    @endforelse
  </div>
  {{ $posts->links('site.partials.pagination') }}
  @endif
</section>
@endsection
