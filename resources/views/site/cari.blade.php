@extends('layouts.site', ['title' => $q !== '' ? 'Cari "'.$q.'"' : 'Cari di situs', 'description' => "Cari jadwal kajian, kitab, ustadz, program, berita, dan layanan Masjid Imam Syafi'i Banjarmasin."])
@if($q !== '')
@push('meta')<meta name="robots" content="noindex">@endpush
@endif
@section('content')
<section class="view">
  @include('site.partials.phead', ['crumb' => 'Cari', 'ar' => 'البحث', 'eyebrow' => 'Pencarian', 'h1' => 'Cari di situs', 'lede' => 'Kajian, kitab, nama ustadz, program, berita, dan layanan dalam satu kotak pencarian.'])
  <form class="toolbar search-page" method="get" action="{{ route('cari') }}" role="search">
    <label class="search" for="cq"><span class="sr-only">Kata kunci</span>@include('site.partials.icon',['name'=>'search','size'=>18])<input id="cq" name="q" type="search" value="{{ $q }}" placeholder="Contoh: tafsir, fikih, BBQ, kurban, ambulans" maxlength="80" @if($q === '') autofocus @endif></label>
    <button class="btn" type="submit">Cari</button>
  </form>

  @if($q !== '' && mb_strlen($q) < 2)
    <p class="callout">Ketik minimal 2 huruf.</p>
  @elseif($q !== '')
    <p class="result-note" role="status">{{ $total }} hasil untuk "<b>{{ $q }}</b>".</p>
    @forelse($groups as $label => $items)
      <section class="sres" aria-labelledby="g-{{ \Illuminate\Support\Str::slug($label) }}">
        <h2 id="g-{{ \Illuminate\Support\Str::slug($label) }}">{{ $label }} <span>{{ $items->count() }}</span></h2>
        <ul>
          @foreach($items as $it)
          <li><a href="{{ $it['url'] }}"><b>{{ $it['title'] }}</b>@if($it['meta'])<small>{{ $it['meta'] }}</small>@endif @if($it['text'])<span>{{ $it['text'] }}</span>@endif</a></li>
          @endforeach
        </ul>
      </section>
    @empty
      <div class="empty-art"><h3>Tidak ada yang cocok</h3><p>Coba kata lain yang lebih umum, misalnya nama kitab atau nama ustadz. Atau buka langsung:</p>
        <div class="row" style="justify-content:center"><a class="btn small ghost" href="{{ route('kajian') }}">Jadwal kajian</a><a class="btn small ghost" href="{{ route('program') }}">Program</a><a class="btn small ghost" href="{{ route('berita') }}">Berita</a></div></div>
    @endforelse
  @else
    <div class="sres-hints"><p class="note">Pencarian yang sering dipakai:</p>
      <div class="row">@foreach(['Tafsir', 'Fikih', 'Sirah', 'Muslimah', 'BBQ', 'Kurban', 'Infak'] as $h)<a class="chip" href="{{ route('cari', ['q' => $h]) }}">{{ $h }}</a>@endforeach</div></div>
  @endif
</section>
@endsection
