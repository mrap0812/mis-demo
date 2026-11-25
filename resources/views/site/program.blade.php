@extends('layouts.site', ['title' => 'Program masjid', 'description' => "Program rutin Masjid Imam Syafi'i Banjarmasin di luar jadwal kajian: belajar baca Al-Qur'an, sedekah sampah, dan kegiatan sosial lainnya."])
@section('content')
<section class="view">
  @include('site.partials.phead', ['crumb' => 'Program', 'ar' => 'البرامج', 'eyebrow' => 'Program', 'h1' => 'Program masjid', 'lede' => 'Kegiatan rutin di luar jadwal kajian. Pilih program untuk melihat jadwal, syarat, dan nomor yang bisa dihubungi.'])
  <div class="progs">
    @forelse($programs as $p)
    <article class="prog-card">
      <a class="prog-thumb" href="{{ route('program.show', $p->slug) }}" tabindex="-1" aria-hidden="true">
        @if($p->poster)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($p->poster) }}"{!! \App\Support\Img::attrs($p->poster, '120px') !!} alt="" width="480" height="600" loading="lazy" decoding="async">
        @else<span class="prog-ph">@include('site.partials.icon',['name'=>$p->icon(),'size'=>40])</span>@endif
      </a>
      <div class="prog-body">
        <p class="prog-cat">@include('site.partials.icon',['name'=>$p->icon(),'size'=>16]) {{ $p->categoryLabel() }}@if($w = $p->whenLabel($today))<span class="prog-when">{{ $w }}</span>@endif</p>
        <h2><a href="{{ route('program.show', $p->slug) }}">{{ $p->title }}</a></h2>
        @if($p->tagline)<p class="prog-tag">{{ $p->tagline }}</p>@endif
        <ul class="prog-facts">
          @if($p->schedule)<li>@include('site.partials.icon',['name'=>'clock','size'=>16]) {{ $p->schedule }}</li>@endif
          @if($p->audience)<li>@include('site.partials.icon',['name'=>'users','size'=>16]) {{ $p->audience }}</li>@endif
        </ul>
        <a class="link-arrow" href="{{ route('program.show', $p->slug) }}">Lihat program @include('site.partials.icon',['name'=>'arrow','size'=>16])</a>
      </div>
    </article>
    @empty
    <p class="callout">Daftar program sedang disiapkan.</p>
    @endforelse
  </div>
</section>
@endsection
