<x-filament-panels::page>
    <form wire:submit="save" style="display:grid;gap:1.5rem">
        {{ $this->form }}
        <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap">
            <x-filament::button type="submit" icon="heroicon-m-check">Simpan pengaturan</x-filament::button>
            <x-filament::link :href="route('home')" target="_blank" icon="heroicon-m-arrow-top-right-on-square">Lihat situs</x-filament::link>
        </div>
    </form>
</x-filament-panels::page>
