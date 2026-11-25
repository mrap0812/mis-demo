@php
  $st = \App\Services\KajianSchedule::status($o); $s = $o['series'];
  $stLabel = ['berlangsung' => 'Sedang berlangsung', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan', 'ditunda' => 'Ditunda'][$st] ?? null;
  $stc = ['berlangsung' => 'ok', 'dibatalkan' => 'bad', 'ditunda' => 'warn'][$st] ?? '';
  $cat = $s->category !== 'kajian' ? (\App\Models\KajianSeries::CATEGORIES[$s->category] ?? null) : null;
@endphp
<a class="evc{{ $st === 'dibatalkan' ? ' cancel' : '' }}{{ $st === 'selesai' ? ' done' : '' }}" style="--c:{{ $s->color }}" href="{{ route('kajian.show', [$s->code, $o['date']->format('Ymd')]) }}">
  <span class="evc-time">{{ $s->slotLabel() }} <span>· ± {{ $o['starts_at']->format('H.i') }} WITA</span></span>
  <h3>{{ $o['title'] }}</h3>
  <p>{{ $o['speaker'] ?? 'Khatib diumumkan di '.$site['info_handle'] }}</p>
  @if($stLabel || $o['source'] === 'poster' || $s->badge || $cat)
  <span class="row">
    @if($stLabel)<span class="chip {{ $stc }}">{{ $stLabel }}</span>@endif
    @if($o['source'] === 'poster')<span class="chip ok">Tema pertemuan</span>@endif
    @if($s->badge)<span class="chip sim">{{ $s->badge }}</span>@endif
    @if($cat)<span class="chip">{{ $cat }}</span>@endif
  </span>
  @endif
</a>
