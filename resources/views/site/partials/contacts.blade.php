@php $wa = fn ($n) => \App\Support\SafeHtml::wa($n); @endphp
<div class="contacts">
  @foreach($site['contacts'] as $c)
  <div class="contact"><span>{{ $c['label'] }}</span><b>{{ $c['number'] }}</b>
    <div class="row"><a class="btn small" href="{{ $wa($c['number']) }}" target="_blank" rel="noopener">@include('site.partials.icon',['name'=>'wa','size'=>16]) Chat</a><button class="copy" type="button" data-copy="{{ \App\Support\SafeHtml::digits($c['number']) }}">Salin</button></div>
  </div>
  @endforeach
</div>
