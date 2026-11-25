@php $full = $site['address'].($site['postal_code'] ? ' '.$site['postal_code'] : ''); $q = $site['latitude'].','.$site['longitude']; @endphp
<div class="card loc"><h2>Lokasi</h2>
  <p>{{ $full }}</p>
  <div class="row"><a class="btn small" href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($q) }}" target="_blank" rel="noopener">Buka di Google Maps</a><button class="copy" type="button" data-copy="{{ $full }}">Salin alamat</button></div>
  <div class="mapbox" data-map="https://maps.google.com/maps?q={{ urlencode($q) }}&amp;z=17&amp;output=embed">
    <button class="btn small ghost" type="button">@include('site.partials.icon',['name'=>'pin','size'=>16]) Tampilkan peta di sini</button>
    <p class="note">Peta dimuat dari Google hanya bila tombol ditekan, agar halaman tetap ringan.</p>
  </div>
</div>
