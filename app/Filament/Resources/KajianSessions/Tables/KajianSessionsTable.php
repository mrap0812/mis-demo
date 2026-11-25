<?php

namespace App\Filament\Resources\KajianSessions\Tables;

use App\Models\KajianSession;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KajianSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->label('Tanggal')->date('D, d M Y')->sortable(),
                TextColumn::make('series.title')->label('Seri')->wrap(),
                TextColumn::make('title')->label('Judul pertemuan')->placeholder('Sama dengan seri')->wrap()->searchable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => KajianSession::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => ['terjadwal' => 'info', 'ditunda' => 'warning', 'dibatalkan' => 'danger'][$state] ?? 'gray'),
                TextColumn::make('source')->label('Sumber')->badge()->color('gray'),
            ])
            ->filters([SelectFilter::make('status')->options(KajianSession::STATUSES)])
            ->recordActions([EditAction::make()]);
    }
}
