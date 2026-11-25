<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>{{ $s['maintenance_title'] }} | Masjid Imam Syafi'i Banjarmasin</title>
  <link rel="icon" href="{{ asset('images/logo-red.png') }}">
  <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ @filemtime(public_path('css/site.css')) }}">
  <style>
    .mt{min-height:100vh;display:grid;place-items:center;padding:24px;background:var(--deep);color:var(--on-deep)}
    .mt-card{max-width:560px;text-align:center;display:grid;gap:16px;justify-items:center}
    .mt-card h1{font-size:clamp(1.7rem,5vw,2.4rem);margin:0;color:inherit}
    .mt-card p{margin:0;font-size:1.05rem;line-height:1.6;opacity:.92}
    .mt-ar{font-size:1.4rem;opacity:.85}
  </style>
</head>
<body>
<main class="mt" id="konten">
  <div class="mt-card">
    <img src="{{ asset('images/logo-white.png') }}" alt="Logo Masjid Imam Syafi'i" width="72" height="52">
    <p class="mt-ar" lang="ar">مسجد الإمام الشافعي</p>
    <h1>{{ $s['maintenance_title'] }}</h1>
    <p>{!! nl2br(e($s['maintenance_text'])) !!}</p>
    @if (filled($s['maintenance_until']))
      <p>Perkiraan selesai: {{ \Carbon\CarbonImmutable::parse($s['maintenance_until'], 'Asia/Makassar')->translatedFormat('l, j F Y \p\u\k\u\l H.i') }} WITA</p>
    @endif
  </div>
</main>
</body>
</html>
