<div class="phead"><div class="wrap phead-in">
  <nav class="crumb2" aria-label="Breadcrumb"><a href="{{ route('home') }}">Beranda</a><span>/</span><span>{{ $crumb }}</span></nav>
  <p class="eyebrow-k light"><span lang="ar">{{ $ar }}</span> {{ $eyebrow }}</p>
  <h1>{{ $h1 }}</h1>
  @if(! empty($lede))<p class="plede">{{ $lede }}</p>@endif
</div></div>
