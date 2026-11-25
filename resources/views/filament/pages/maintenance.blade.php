@php
    $dot = ['ok' => ['#15803d', 'Baik'], 'warn' => ['#b45309', 'Perlu perhatian'], 'fail' => ['#b91c1c', 'Harus diperbaiki']];
    $th = 'text-align:left;padding:.55rem .75rem;font-size:.8rem;font-weight:600;opacity:.75;border-bottom:1px solid rgba(127,127,127,.25)';
    $td = 'padding:.6rem .75rem;border-bottom:1px solid rgba(127,127,127,.15);vertical-align:top';
    $bytes = fn ($b) => \App\Support\Maintenance\Housekeeping::bytes((int) $b);
@endphp
<x-filament-panels::page>
    <div style="display:grid;gap:1.5rem">

        @if ($maintenance)
            <div role="status" style="padding:1rem 1.25rem;border-radius:.75rem;background:#fef3c7;color:#78350f;font-weight:600">
                Mode perbaikan sedang AKTIF. Pengunjung melihat halaman "{{ $site['maintenance_title'] }}".
            </div>
        @endif

        <x-filament::section icon="heroicon-o-heart" :description="$summary['fail'] ? 'Ada '.$summary['fail'].' hal yang harus diperbaiki.' : ($summary['warn'] ? 'Situs berjalan, ada '.$summary['warn'].' hal yang perlu perhatian.' : 'Semua pemeriksaan baik.')">
            <x-slot name="heading">Kesehatan sistem</x-slot>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.92rem">
                    <thead><tr><th scope="col" style="{{ $th }}">Pemeriksaan</th><th scope="col" style="{{ $th }}">Status</th><th scope="col" style="{{ $th }}">Keterangan</th></tr></thead>
                    <tbody>
                    @foreach ($checks as $c)
                        <tr>
                            <td style="{{ $td }};font-weight:600;white-space:nowrap">{{ $c['label'] }}</td>
                            <td style="{{ $td }};white-space:nowrap"><span style="display:inline-flex;align-items:center;gap:.4rem;color:{{ $dot[$c['status']][0] }};font-weight:600"><span aria-hidden="true" style="width:.6rem;height:.6rem;border-radius:999px;background:currentColor"></span>{{ $dot[$c['status']][1] }}</span></td>
                            <td style="{{ $td }}">{{ $c['detail'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-wrench-screwdriver" description="Semua tindakan tercatat di Log aktivitas.">
            <x-slot name="heading">Tindakan cepat</x-slot>
            <div style="display:flex;flex-wrap:wrap;gap:.75rem">
                {{ $this->maintenanceAction }}
                {{ $this->clearCacheAction }}
                {{ $this->tidyAction }}
                {{ $this->thumbsAction }}
                @if (config('filesystems.links'))
                    {{ $this->storageLinkAction }}
                @endif
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-circle-stack" :description="'Disimpan di server, '.config('mis.backup_keep').' cadangan terbaru. Unduh dan simpan salinan di tempat lain secara berkala.'">
            <x-slot name="heading">Cadangan database</x-slot>
            @if ($backups)
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.92rem">
                        <thead><tr><th scope="col" style="{{ $th }}">File</th><th scope="col" style="{{ $th }}">Dibuat</th><th scope="col" style="{{ $th }}">Ukuran</th><th scope="col" style="{{ $th }}"><span class="sr-only">Tindakan</span></th></tr></thead>
                        <tbody>
                        @foreach ($backups as $b)
                            <tr>
                                <td style="{{ $td }};font-family:ui-monospace,monospace">{{ $b['name'] }}</td>
                                <td style="{{ $td }}">{{ \Carbon\Carbon::createFromTimestamp($b['time'], 'Asia/Makassar')->translatedFormat('j M Y, H.i') }}</td>
                                <td style="{{ $td }}">{{ $bytes($b['size']) }}</td>
                                <td style="{{ $td }};white-space:nowrap;text-align:right">
                                    <x-filament::link tag="button" wire:click="download('{{ $b['name'] }}')" icon="heroicon-m-arrow-down-tray">Unduh</x-filament::link>
                                    &nbsp; {{ ($this->deleteBackupAction)(['name' => $b['name']]) }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p>Belum ada cadangan. Tekan "Buat cadangan sekarang" di kanan atas.</p>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-arrow-path" :description="$pending ? 'Ada pembaruan struktur database dari versi aplikasi yang baru diunggah.' : 'Tidak ada pembaruan tertunda.'">
            <x-slot name="heading">Migrasi database</x-slot>
            @if ($pending)
                <ul style="margin:0 0 1rem;padding-left:1.2rem;font-family:ui-monospace,monospace;font-size:.85rem">
                    @foreach ($pending as $m)<li>{{ $m }}</li>@endforeach
                </ul>
                {{ $this->migrateAction }}
            @else
                <p>Struktur database sudah sesuai dengan versi aplikasi ini.</p>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-folder" description="Membantu memantau kuota hosting.">
            <x-slot name="heading">Ukuran folder</x-slot>
            @if ($sizes === null)
                <x-filament::button color="gray" wire:click="loadSizes" icon="heroicon-m-calculator">Hitung ukuran folder</x-filament::button>
            @else
                <table style="width:100%;border-collapse:collapse;font-size:.92rem">
                    <thead><tr><th scope="col" style="{{ $th }}">Isi</th><th scope="col" style="{{ $th }}">Jumlah file</th><th scope="col" style="{{ $th }}">Ukuran</th></tr></thead>
                    <tbody>
                    @foreach ($sizes as $s)
                        <tr><td style="{{ $td }}">{{ $s['label'] }}</td><td style="{{ $td }}">{{ number_format($s['files'], 0, ',', '.') }}</td><td style="{{ $td }};font-weight:600">{{ $bytes($s['size']) }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-exclamation-triangle" description="15 catatan error terbaru dari log server." collapsible :collapsed="! $logErrors">
            <x-slot name="heading">Error terbaru</x-slot>
            @forelse ($logErrors as $e)
                <div style="padding:.5rem 0;border-bottom:1px solid rgba(127,127,127,.15);font-size:.85rem">
                    <b>{{ $e['time'] }}</b> · {{ $e['level'] }}<br>
                    <span style="font-family:ui-monospace,monospace;word-break:break-word">{{ $e['message'] }}</span>
                </div>
            @empty
                <p>Tidak ada error tercatat.</p>
            @endforelse
        </x-filament::section>
    </div>
</x-filament-panels::page>
