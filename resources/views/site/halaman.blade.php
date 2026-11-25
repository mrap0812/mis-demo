@extends('layouts.site', ['title' => $page->title, 'description' => $page->lede])
@section('content')
<section class="view">
  @include('site.partials.phead', ['crumb' => $page->title, 'ar' => 'عن المسجد', 'eyebrow' => 'Tentang masjid', 'h1' => $page->title, 'lede' => $page->lede])
  @if($page->cover)<div class="profile-hero"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($page->cover) }}"{!! \App\Support\Img::attrs($page->cover, '100vw') !!} alt="" width="1600" height="540" decoding="async"></div>@endif
  <div class="prose">{!! $page->safeBody() !!}</div>
  <p><a class="link-arrow" href="{{ route('profil') }}">Kembali ke profil masjid</a></p>
</section>
@endsection
