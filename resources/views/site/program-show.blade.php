@php
  $poster = $program->poster ? \Illuminate\Support\Facades\Storage::disk('public')->url($program->poster) : null;
  $wa = \App\Support\SafeHtml::wa($program->contact_number);
  $items = $program->list('items'); $notes = $program->list('notes'); $steps = $program->list('steps');
@endphp
@extends('layouts.site', ['title' => $program->title, 'description' => $program->summary, 'ogImage' => $poster])
@section('content')
<section class="view">
  <div class="phead"><div class="wrap phead-in">
    <nav class="crumb2" aria-label="Breadcrumb"><a href="{{ route('home') }}">Beranda</a><span>/</span><a href="{{ route('program') }}">Program</a><span>/</span><span>{{ $program->title }}</span></nav>
    <p class="eyebrow-k light">{{ $program->categoryLabel() }}</p>
    <h1>{{ $program->title }}</h1>
    @if($program->tagline)<p class="plede">{{ $program->tagline }}</p>@endif
    <div class="prog-chips">
      @if($program->schedule)<span>@include('site.partials.icon',['name'=>'clock','size'=>16]) {{ $program->schedule }}</span>@endif
      @if($program->is_free)<span>Gratis</span>@endif
      @if($w = $program->whenLabel($today))<span class="prog-when-hero">{{ $w }}</span>@endif
    </div>
    @if($wa)<a class="btn gold prog-hero-cta" href="{{ $wa }}" target="_blank" rel="noopener">@include('site.partials.icon',['name'=>'wa','size'=>18]) {{ $program->contact_label ?: 'Hubungi' }}</a>@endif
  </div></div>

  <div class="prog-grid">
    <div class="prog-main">
      <section class="prog-sec" aria-labelledby="apa-h">
        <h2 id="apa-h">Tentang program</h2>
        <p class="prog-lead">{{ $program->summary }}</p>
        <dl class="prog-dl">
          @if($program->schedule)<div><dt>@include('site.partials.icon',['name'=>'cal','size'=>18]) Waktu</dt><dd>{{ $program->schedule }}</dd></div>@endif
          @if($program->location)<div><dt>@include('site.partials.icon',['name'=>'pin','size'=>18]) Tempat</dt><dd>{{ $program->location }}</dd></div>@endif
          @if($program->audience)<div><dt>@include('site.partials.icon',['name'=>'users','size'=>18]) Untuk</dt><dd>{{ $program->audience }}</dd></div>@endif
          @if($program->organizer)<div><dt>@include('site.partials.icon',['name'=>'home','size'=>18]) Penyelenggara</dt><dd>@if($program->organizer_url)<a href="{{ $program->organizer_url }}" target="_blank" rel="noopener">{{ $program->organizer }}</a>@else{{ $program->organizer }}@endif</dd></div>@endif
        </dl>
        @if($program->body)<div class="prose">{!! $program->safeBody() !!}</div>@endif
      </section>

      @if($items)
      <section class="prog-sec" aria-labelledby="items-h">
        <h2 id="items-h">{{ $program->items_title ?: 'Rincian' }}</h2>
        <ol class="prog-items">
          @foreach($items as $it)
          <li><h3>{{ $it['title'] ?? '' }}</h3>@if(! empty($it['text']))<p>{{ $it['text'] }}</p>@endif</li>
          @endforeach
        </ol>
      </section>
      @endif

      @if($notes)
      <section class="prog-sec" aria-labelledby="notes-h">
        <h2 id="notes-h">{{ $program->notes_title ?: 'Ketentuan' }}</h2>
        <ul class="prog-check">
          @foreach($notes as $n)<li>@include('site.partials.icon',['name'=>'check','size'=>20])<span>{{ $n }}</span></li>@endforeach
        </ul>
      </section>
      @endif

      @if($steps)
      <section class="prog-sec" aria-labelledby="steps-h">
        <h2 id="steps-h">Alur kegiatan</h2>
        <ol class="prog-steps">
          @foreach($steps as $s)<li><span>{{ $s }}</span></li>@endforeach
        </ol>
      </section>
      @endif

      @if($program->closing)
      <blockquote class="prog-closing">@include('site.partials.icon',['name'=>'leaf','size'=>22])<p>{{ $program->closing }}</p></blockquote>
      @endif
    </div>

    <aside class="prog-side" aria-label="Kontak dan poster">
      <div class="card prog-cta">
        <h2>Ingin bertanya atau ikut?</h2>
        @if($wa)<a class="btn gold" href="{{ $wa }}" target="_blank" rel="noopener">@include('site.partials.icon',['name'=>'wa','size'=>18]) {{ $program->contact_label ?: 'Hubungi' }}: {{ $program->contact_number }}</a>@endif
        @if($program->registration_url)<a class="btn ghost" href="{{ $program->registration_url }}" target="_blank" rel="noopener">Daftar</a>@endif
        @if(! $wa && ! $program->registration_url)<a class="btn ghost" href="{{ route('kontak') }}">Hubungi sekretariat</a>@endif
        <button class="btn ghost small" type="button" data-share data-title="{{ $program->title }}" data-url="{{ url()->current() }}" hidden>@include('site.partials.icon',['name'=>'share','size'=>16]) Bagikan</button>
      </div>
      @if($poster)
      <figure class="prog-poster">
        <a href="{{ $poster }}" target="_blank" rel="noopener"><img src="{{ $poster }}"{!! \App\Support\Img::attrs($program->poster, '(max-width: 899px) 420px, 320px') !!} alt="{{ $program->poster_alt ?: 'Poster '.$program->title }}" width="560" height="700" loading="lazy" decoding="async"></a>
        <figcaption>Poster resmi. Ketuk untuk memperbesar.</figcaption>
      </figure>
      @endif
    </aside>
  </div>

  @if($others->isNotEmpty())
  <section class="sec" aria-labelledby="lain-h">
    <div class="sec-h"><div><h2 id="lain-h">Program lainnya</h2></div><a class="link-arrow" href="{{ route('program') }}">Semua program</a></div>
    <ul class="prog-mini">
      @foreach($others as $o)
      <li><a href="{{ route('program.show', $o->slug) }}"><span class="prog-mini-ic">@include('site.partials.icon',['name'=>$o->icon(),'size'=>20])</span><span><b>{{ $o->title }}</b>@if($o->schedule)<small>{{ $o->schedule }}</small>@endif</span></a></li>
      @endforeach
    </ul>
  </section>
  @endif
  @if($program->source)<p class="src-note">Sumber: {{ $program->source }}.</p>@endif
</section>
@endsection
