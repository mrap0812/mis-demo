@extends('layouts.site', ['title' => 'Layanan jamaah', 'description' => "Layanan jamaah Masjid Imam Syafi'i Banjarmasin dan nomor kontak tiap unit."])
@php $wa = fn ($n) => \App\Support\SafeHtml::wa($n); @endphp
@section('content')
<section class="view">
  @include('site.partials.phead', ['crumb' => 'Layanan', 'ar' => 'الخدمات', 'eyebrow' => 'Layanan', 'h1' => 'Layanan jamaah', 'lede' => 'Untuk keperluan yang belum tercantum, silakan hubungi sekretariat lewat nomor di bagian bawah halaman ini.'])
  @if($services->contains('status', 'menunggu'))
  <p class="callout svc-callout">Syarat, jam, dan petugas sebagian layanan masih menunggu penetapan pengurus. Untuk sementara, tanyakan langsung lewat tombol di tiap kartu.</p>
  @endif
  <div class="services">
    @forelse($services as $s)
    <article class="svc" id="{{ $s->slug }}">
      <span class="svc-ic">@include('site.partials.icon',['name'=>$s->icon,'size'=>22])</span>
      <h3>{{ $s->name }}</h3>
      <p>{{ $s->summary }}</p>
      @if($s->schedule)<p class="note"><b>Waktu:</b> {{ $s->schedule }}</p>@endif
      @if($s->details)<details class="svc-more"><summary>Syarat &amp; alur</summary><div class="prose">{!! $s->safeDetails() !!}</div></details>@endif
      @if($s->status === 'menunggu')<p class="svc-pending">Syarat dan jam belum ditetapkan</p>@endif
      <div class="row">
        @if($s->registration_url)<a class="btn small" href="{{ $s->registration_url }}" target="_blank" rel="noopener">Daftar</a>@endif
        @if($s->contact_number)<a class="btn small ghost" href="{{ $wa($s->contact_number) }}" target="_blank" rel="noopener">@include('site.partials.icon',['name'=>'wa','size'=>16]) {{ $s->contact_label ?: 'Hubungi' }}</a>
        @else<a class="btn small ghost" href="#kontak">Tanya sekretariat</a>@endif
      </div>
    </article>
    @empty
    <p class="callout">Daftar layanan sedang diperbarui.</p>
    @endforelse
  </div>
  <section class="sec" id="kontak" aria-labelledby="ct-h">
    <div class="sec-h"><div><p class="eyebrow-k"><span lang="ar">التواصل</span> Kontak</p><h2 id="ct-h">Hubungi unit yang tepat</h2></div><a class="link-arrow" href="{{ route('kontak') }}">Lokasi &amp; media sosial</a></div>
    @include('site.partials.contacts')
  </section>
</section>
@endsection
