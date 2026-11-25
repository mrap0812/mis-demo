@extends('layouts.site', ['title' => 'Penyiapan admin pertama'])
@push('meta')<meta name="robots" content="noindex">@endpush
@section('content')
<section class="view">
  <div class="phead"><div class="wrap phead-in"><p class="eyebrow-k light">Instalasi</p><h1>Buat akun admin pertama</h1>
    <p class="plede">Halaman ini hanya muncul sekali. Setelah akun dibuat, halaman ini hilang dengan sendirinya.</p></div></div>
  <form method="post" action="{{ url('/pasang') }}" class="setup-form" novalidate>
    @csrf
    @if ($errors->any())
      <div class="setup-err" role="alert"><b>Periksa lagi:</b><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <label>Token penyiapan <small>(nilai SETUP_TOKEN di file .env)</small><input name="token" type="password" autocomplete="off" required></label>
    <label>Nama lengkap<input name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="80"></label>
    <label>Email (dipakai untuk masuk)<input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required></label>
    <label>Kata sandi <small>(minimal 12 karakter, ada huruf dan angka)</small><input name="password" type="password" autocomplete="new-password" required minlength="12"></label>
    <label>Ulangi kata sandi<input name="password_confirmation" type="password" autocomplete="new-password" required></label>
    <button class="btn gold" type="submit">Buat akun admin</button>
  </form>
</section>
@endsection
