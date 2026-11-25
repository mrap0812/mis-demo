@php $rp = fn ($n) => \App\Models\Campaign::rupiah((int) $n); @endphp
<div class="camps">
  @foreach($campaigns as $c)
  @php $acc = $c->liveAccount(); @endphp
  <article class="camp card" aria-labelledby="camp-{{ $c->id }}">
    @if($c->poster)<a class="camp-poster" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($c->poster) }}" target="_blank" rel="noopener"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($c->poster) }}"{!! \App\Support\Img::attrs($c->poster, '120px') !!} alt="{{ $c->poster_alt ?: 'Poster '.$c->title }}" width="480" height="600" loading="lazy" decoding="async"></a>@endif
    <div class="camp-body">
      @if($c->kind === 'ramadhan')
        @php $days = $c->daysToRamadhan($today); @endphp
        <p class="n-meta">{{ $days > 0 ? 'Hitung mundur' : 'Sedang berlangsung' }}</p>
        <h3 id="camp-{{ $c->id }}">{{ $c->title }}</h3>
        @if($days > 0)<p class="camp-count"><b>&plusmn; {{ $days }}</b> hari menuju 1 Ramadhan {{ $c->hijri_year }} H<small>Perkiraan kalender Umm al-Qura, {{ $c->ramadhanStart()->translatedFormat('j F Y') }}. Penetapan resmi menunggu pengumuman.</small></p>
        @else<p class="camp-count"><b>Ramadhan {{ $c->hijri_year }} H</b> sedang berlangsung</p>@endif
      @else
        <p class="n-meta">Kampanye donasi</p>
        <h3 id="camp-{{ $c->id }}">{{ $c->title }}</h3>
        @if($c->target_amount)
        <div class="camp-bar" role="img" aria-label="Terkumpul {{ number_format($c->percent(), 2, ',', '.') }} persen dari target"><i style="--v:{{ max(1, $c->percent()) }}%"></i></div>
        <dl class="camp-nums">
          <div><dt>Terkumpul ({{ number_format($c->percent(), 2, ',', '.') }}%)</dt><dd>{{ $rp($c->collected_amount) }}</dd></div>
          <div><dt>Masih diperlukan</dt><dd class="need">{{ $rp($c->remaining()) }}</dd></div>
          <div><dt>Target</dt><dd>{{ $rp($c->target_amount) }}</dd></div>
        </dl>
        @endif
      @endif
      @if($c->summary)<p class="camp-sum">{{ $c->summary }}</p>@endif
      @if($c->quote)<p class="camp-quote">&ldquo;{{ $c->quote }}&rdquo;@if($c->quote_ref) <span>({{ $c->quote_ref }})</span>@endif</p>@endif
      <div class="camp-acc">
        @if($acc)
          <span>{{ $acc->bank_name }} <b>{{ $acc->account_number }}</b> a.n. {{ $acc->account_holder }}</span>
          <button class="btn small" type="button" data-copy="{{ \App\Support\SafeHtml::digits($acc->account_number) }}">Salin nomor</button>
        @elseif($c->poster_account_number)
          <span>{{ $c->poster_bank }} <b>{{ $c->poster_account_number }}</b> a.n. {{ $c->poster_account_holder }}<small>Sesuai poster resmi</small></span>
          <button class="btn small" type="button" data-copy="{{ \App\Support\SafeHtml::digits($c->poster_account_number) }}">Salin nomor</button>
        @else
          <span>Rekening kampanye ini sedang diverifikasi pengurus. Untuk sementara, tanyakan ke <a href="{{ route('kontak') }}">sekretariat</a>.</span>
        @endif
      </div>
      <p class="src-note">@if($c->data_date)Angka per {{ $c->data_date->translatedFormat('j F Y') }}. @endif{{ $c->source }}</p>
    </div>
  </article>
  @endforeach
</div>
