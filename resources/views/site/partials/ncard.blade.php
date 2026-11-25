@php
  $img = $p->cover ? \Illuminate\Support\Facades\Storage::disk('public')->url($p->cover) : asset('images/facade.jpg');
  $excerpt ??= true;
@endphp
<article class="ncard{{ $p->type === 'pengumuman' ? ' poster' : '' }}"><img src="{{ $img }}"{!! \App\Support\Img::attrs($p->cover, $sizes ?? '(max-width: 720px) 92vw, 380px') !!} alt="" width="1200" height="750" loading="lazy" decoding="async"><div class="nbody">
  <p class="nmeta"><span class="cat">{{ $p->category ?: \App\Models\Post::TYPES[$p->type] }}</span>{{ $p->published_at->translatedFormat('j F Y') }}</p>
  <h3><a href="{{ route('berita.show', $p->slug) }}">{{ $p->title }}</a></h3>@if($excerpt && $p->excerpt)<p>{{ \Illuminate\Support\Str::limit($p->excerpt, 150) }}</p>
  <span class="link-arrow" aria-hidden="true">Baca selengkapnya</span>@endif</div></article>
