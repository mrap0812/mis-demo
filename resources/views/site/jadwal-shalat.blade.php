@extends('layouts.site', ['title' => 'Jadwal shalat '.$month->translatedFormat('F Y'), 'description' => "Jadwal shalat bulanan untuk Masjid Imam Syafi'i Banjarmasin (WITA), lengkap dengan tanggal Hijriah. Bisa dicetak."])
@php use App\Services\PrayerTimes; @endphp
@section('content')
<section class="view">
  @include('site.partials.phead', ['crumb' => 'Jadwal shalat', 'ar' => 'مواقيت الصلاة', 'eyebrow' => 'Waktu shalat', 'h1' => 'Jadwal shalat '.$month->translatedFormat('F Y'), 'lede' => $hijriRange.'. Waktu dalam WITA untuk lokasi masjid di Pemurus Dalam, Banjarmasin.'])

  <div class="ps-bar">
    <nav class="ps-nav" aria-label="Pilih bulan">
      @if($prev)<a class="btn small ghost" href="{{ route('shalat', ['bulan' => $prev->format('Y-m')]) }}" rel="prev">{{ $prev->translatedFormat('F') }}</a>@endif
      @unless($isCurrent)<a class="btn small ghost" href="{{ route('shalat') }}">Bulan ini</a>@endunless
      @if($next)<a class="btn small ghost" href="{{ route('shalat', ['bulan' => $next->format('Y-m')]) }}" rel="next">{{ $next->translatedFormat('F') }}</a>@endif
    </nav>
    <button class="btn small" type="button" data-print>Cetak jadwal</button>
  </div>

  <div class="ps-wrap" tabindex="0" role="region" aria-label="Tabel jadwal shalat {{ $month->translatedFormat('F Y') }}">
    <table class="ps-table">
      <caption class="sr-only">Jadwal shalat {{ $month->translatedFormat('F Y') }}, waktu WITA</caption>
      <thead><tr><th scope="col">Tanggal</th><th scope="col" class="ps-hcol">Hijriah</th><th scope="col" class="ps-aux">Imsak</th><th scope="col">Subuh</th><th scope="col" class="ps-aux">Terbit</th><th scope="col">Dzuhur</th><th scope="col">Ashar</th><th scope="col">Maghrib</th><th scope="col">Isya</th></tr></thead>
      <tbody>
        @foreach($rows as $r)
        <tr class="{{ $r['today'] ? 'is-today' : '' }}{{ $r['date']->dayOfWeek === 5 ? ' is-jumat' : '' }}" @if($r['today']) aria-current="date" @endif>
          <th scope="row"><span class="ps-dow">{{ $r['date']->translatedFormat('D') }}</span> {{ $r['date']->day }}<small class="ps-hm">{{ $r['hijri'] }}</small></th>
          <td class="ps-h">{{ $r['hijri'] }}</td>
          @foreach(['imsak', 'fajr', 'sunrise', 'dhuhr', 'asr', 'maghrib', 'isha'] as $k)<td @class(['ps-aux' => in_array($k, ['imsak', 'sunrise'])])>{{ PrayerTimes::hhmm($r['t'][$k]) }}</td>@endforeach
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <p class="src-note">Dihitung secara astronomis dengan parameter Kemenag RI (Subuh 20°, Isya 18°, ihtiyath 2 menit) untuk koordinat masjid. Imsak 10 menit sebelum Subuh. Di layar ponsel, kolom Imsak dan Terbit disembunyikan agar kelima waktu shalat terlihat; keduanya tetap ada saat dicetak atau di layar lebar. Selisih dengan jadwal resmi Kemenag umumnya tidak lebih dari 1 menit. Sudah dicocokkan dengan jadwal masjid.</p>
</section>
@endsection
