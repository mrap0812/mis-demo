@php $d = $list->first()['date']; $diff = (int) $today->diffInDays($d, false); @endphp
<section class="agenda-day" aria-labelledby="d{{ $d->format('Ymd') }}">
  <h2 class="agenda-h" id="d{{ $d->format('Ymd') }}"><span class="ad-num" aria-hidden="true">{{ $d->day }}</span><span><b>{{ $d->translatedFormat('l') }}</b> {{ $d->translatedFormat('j F Y') }}</span>@if($diff === 0)<em>Hari ini</em>@elseif($diff === 1)<em class="soft">Besok</em>@endif</h2>
  <div class="agenda-items">@foreach($list as $o) @include('site.partials.evcard', ['o' => $o]) @endforeach</div>
</section>
