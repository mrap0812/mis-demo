@php
  $cover = $post->cover ? \Illuminate\Support\Facades\Storage::disk('public')->url($post->cover) : null;
  $url = route('berita.show', $post->slug);
  $label = $post->category ?: \App\Models\Post::TYPES[$post->type];
@endphp
@extends('layouts.site', ['title' => $post->title, 'description' => $post->excerpt, 'ogImage' => $cover, 'ogType' => 'article'])
@push('meta')
<meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
<meta property="article:section" content="{{ $label }}">
@endpush
@section('content')
<article class="view article">
  <header class="art-head">
    <nav class="crumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Beranda</a> / <a href="{{ route('berita') }}">Berita</a></nav>
    <p class="art-meta"><a class="cat" href="{{ route('berita', ['jenis' => $post->type]) }}">{{ $label }}</a><time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->translatedFormat('l, j F Y') }}</time><span>± {{ $post->readingMinutes() }} menit baca</span></p>
    <h1>{{ $post->title }}</h1>
    @if($post->excerpt)<p class="art-lede">{{ $post->excerpt }}</p>@endif
  </header>
  @if($cover)<figure class="art-cover"><img src="{{ $cover }}"{!! \App\Support\Img::attrs($post->cover, '(max-width: 1100px) 100vw, 1040px') !!} alt="" width="1200" height="675" decoding="async" fetchpriority="high"></figure>@endif

  <div class="art-grid">
    <div class="prose">{!! $post->safeBody((bool) $cover) !!}</div>
    <aside class="art-side" aria-label="Bagikan">
      <div class="share">
        <p class="share-t">Bagikan</p>
        <a class="btn small" href="https://wa.me/?text={{ rawurlencode($post->title.' '.$url) }}" target="_blank" rel="noopener">@include('site.partials.icon',['name'=>'wa','size'=>16]) WhatsApp</a>
        <button class="btn small ghost" type="button" data-copy="{{ $url }}">@include('site.partials.icon',['name'=>'link','size'=>16]) Salin tautan</button>
        <button class="btn small ghost" type="button" data-share data-title="{{ $post->title }}" data-url="{{ $url }}" hidden>@include('site.partials.icon',['name'=>'share','size'=>16]) Lainnya</button>
      </div>
    </aside>
  </div>

  @if($post->source_url && str_contains($post->source_url, 'mis.id/') && ! str_contains($post->source_url, '?p='))
    <p class="src-note">Pertama kali terbit di situs lama mis.id.</p>
  @endif

  @if($prev || $next)
  <nav class="art-nav" aria-label="Tulisan lain">
    @if($prev)<a class="art-nav-a" href="{{ route('berita.show', $prev->slug) }}" rel="prev"><small>Sebelumnya</small><b>{{ $prev->title }}</b></a>@else<span></span>@endif
    @if($next)<a class="art-nav-a is-next" href="{{ route('berita.show', $next->slug) }}" rel="next"><small>Berikutnya</small><b>{{ $next->title }}</b></a>@endif
  </nav>
  @endif

  @if($related->isNotEmpty())
  <section class="sec art-related" aria-labelledby="rel-h">
    <div class="sec-h"><div><h2 id="rel-h">Berita lainnya</h2></div><a class="link-arrow" href="{{ route('berita') }}">Semua berita</a></div>
    <div class="news-grid compact-m">
      @foreach($related as $p)
        @include('site.partials.ncard', ['p' => $p, 'excerpt' => false, 'sizes' => '(max-width: 899px) 104px, 380px'])
      @endforeach
    </div>
  </section>
  @endif
</article>
@endsection

@push('jsonld')
<script type="application/ld+json">{!! json_encode(array_filter([
  '@context' => 'https://schema.org', '@type' => 'NewsArticle',
  'headline' => $post->title, 'datePublished' => $post->published_at->toIso8601String(), 'dateModified' => $post->updated_at->toIso8601String(),
  'image' => $cover, 'description' => $post->excerpt, 'mainEntityOfPage' => $url,
  'publisher' => ['@type' => 'Organization', 'name' => "Masjid Imam Syafi'i Banjarmasin", 'logo' => ['@type' => 'ImageObject', 'url' => asset('images/logo-red.png')]],
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush
