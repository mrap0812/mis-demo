@extends('layouts.site', ['title' => 'Halaman tidak ditemukan'])
@section('content')
<section class="view">
  <div class="phead"><div class="wrap phead-in"><p class="eyebrow-k light"><span lang="ar">عذرا</span> 404</p><h1>Halaman tidak ditemukan</h1>
    <p class="plede">Tautan ini mungkin sudah dipindah, atau kajian pada tanggal itu memang tidak ada di jadwal.</p></div></div>
  <div class="nf">
    <form class="nf-search" method="get" action="{{ route('cari') }}" role="search">
      <label class="search" for="nf-q"><span class="sr">Cari di situs</span>@include('site.partials.icon',['name'=>'search','size'=>18])<input id="nf-q" name="q" type="search" placeholder="Cari kajian, kitab, ustadz, atau berita" minlength="2"></label>
      <button class="btn" type="submit">Cari</button>
    </form>
    <p class="nf-or">Atau langsung ke halaman yang paling sering dibuka:</p>
    <div class="nf-links">
      @foreach([['home','home','Beranda','Jadwal shalat & kajian hari ini'],['kajian','book','Jadwal kajian','Pekan ini dan pekan depan'],['berita','news','Berita','Kabar dan pengumuman masjid'],['program','spark','Program masjid','BBQ, Sedekah Sampah, UMKM'],['infak','gift','Infak','Rekening & QRIS resmi'],['kontak','phone','Kontak','Nomor unit dan lokasi']] as [$r,$ic,$l,$d])
      <a class="qcard" href="{{ route($r) }}">@include('site.partials.icon',['name'=>$ic,'size'=>22])<b>{{ $l }}</b><span>{{ $d }}</span></a>
      @endforeach
    </div>
  </div>
</section>
@endsection
