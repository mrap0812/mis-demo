<x-filament-panels::page>
    <p style="color:var(--gray-500);margin-top:-.5rem">Kajian 14 hari ke depan dari jadwal rutin dan data per pertemuan. Klik <b>Isi detail</b> untuk menambah judul, poster, atau pemateri pengganti; klik <b>Batalkan</b> untuk mengumumkan pembatalan satu pertemuan tanpa mengubah jadwal rutin.</p>
    @foreach ($days as $date => $items)
        @php $d = \Carbon\CarbonImmutable::parse($date); @endphp
        <x-filament::section :heading="$d->translatedFormat('l, j F Y')" :description="$d->isToday() ? 'Hari ini' : null" compact>
            <div style="display:grid;gap:.5rem">
                @foreach ($items as $o)
                    <div style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between;padding:.6rem .75rem;border-radius:.75rem;border-left:4px solid {{ $o['series']->color }};background:rgba(127,127,127,.06)">
                        <div style="min-width:0;flex:1 1 320px">
                            <div style="font-size:.75rem;opacity:.7">{{ $o['series']->slotLabel() }} · {{ \App\Models\KajianSeries::CATEGORIES[$o['series']->category] ?? '' }}</div>
                            <div style="font-weight:600">{{ $o['title'] }}</div>
                            <div style="font-size:.85rem;opacity:.75">{{ $o['speaker'] ?? 'Khatib/pemateri belum diisi' }}</div>
                        </div>
                        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                            @php $c = ['terjadwal' => 'info', 'berlangsung' => 'success', 'selesai' => 'gray', 'dibatalkan' => 'danger', 'ditunda' => 'warning'][$o['status_now']] ?? 'gray'; @endphp
                            <x-filament::badge :color="$c">{{ ucfirst($o['status_now']) }}</x-filament::badge>
                            @if ($o['source'] === 'poster')<x-filament::badge color="success">Ada data pertemuan</x-filament::badge>@endif
                            <x-filament::button tag="a" :href="$o['edit_url']" size="sm" color="gray" icon="heroicon-m-pencil-square">Isi detail</x-filament::button>
                            @if (! in_array($o['status_now'], ['dibatalkan', 'selesai']))
                                <x-filament::button tag="a" :href="$o['cancel_url']" size="sm" color="danger" outlined icon="heroicon-m-x-circle">Batalkan</x-filament::button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endforeach
</x-filament-panels::page>
