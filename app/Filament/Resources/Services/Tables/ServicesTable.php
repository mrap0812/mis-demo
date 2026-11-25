<?php

namespace App\Filament\Resources\Services\Tables;

use App\Models\Service;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')->label('Layanan')->searchable()->weight('bold')->description(fn (Service $r) => $r->summary),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'tersedia' ? 'Tersedia' : 'Menunggu pengurus')
                    ->color(fn (string $state) => $state === 'tersedia' ? 'success' : 'warning'),
                TextColumn::make('contact_number')->label('Kontak')->placeholder('Ke halaman Kontak'),
                IconColumn::make('is_active')->label('Tampil')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }
}
