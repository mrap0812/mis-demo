@extends('layouts.site', ['title' => 'Kontak & lokasi', 'description' => "Nomor WhatsApp, alamat, peta, dan akun media sosial resmi Masjid Imam Syafi'i Banjarmasin."])
@section('content')
<section class="view">
  @include('site.partials.phead', ['crumb' => 'Kontak', 'ar' => 'التواصل', 'eyebrow' => 'Kontak', 'h1' => 'Kontak & lokasi', 'lede' => 'Pilih unit sesuai keperluan agar pertanyaan cepat terjawab. Semua nomor di bawah adalah nomor WhatsApp.'])
  <div class="prof-grid">
    <section aria-labelledby="ct-h"><h2 id="ct-h" class="sr-only">Nomor kontak</h2>@include('site.partials.contacts')</section>
    <aside class="prof-side">
      @include('site.partials.location')
      <div class="card"><h2>Akun resmi</h2>
        <ul class="social-list">
          @foreach($site['socials'] as $s)
          <li><a href="{{ $s['url'] }}" target="_blank" rel="noopener">@include('site.partials.icon',['name'=>['facebook'=>'fb','youtube'=>'yt','instagram'=>'ig','tiktok'=>'tiktok'][$s['platform']] ?? 'link','size'=>18])<span><b>{{ $s['handle'] }}</b>@if($s['note'])<small>{{ $s['note'] }}</small>@endif</span></a></li>
          @endforeach
        </ul>
        <p class="note">Waspadai akun yang mengatasnamakan masjid di luar daftar ini.</p></div>
    </aside>
  </div>
</section>
@endsection
